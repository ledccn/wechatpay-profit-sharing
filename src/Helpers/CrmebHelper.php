<?php

namespace Ledc\WechatPayProfitSharing\Helpers;

use app\dao\user\UserBrokerageDao;
use app\model\order\StoreOrder;
use app\model\user\User;
use app\model\user\UserExtract;
use app\services\user\UserBrokerageServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use ErrorException;
use InvalidArgumentException;
use Ledc\WechatPayProfitSharing\Contracts\Receiver;
use Ledc\WechatPayProfitSharing\Contracts\ReceiverTypeEnums;
use Ledc\WechatPayProfitSharing\Exceptions\AutoProfitSharingException;
use Ledc\WechatPayProfitSharing\Exceptions\HttpException;
use Ledc\WechatPayProfitSharing\Utils;
use think\db\exception\DataNotFoundException;
use think\facade\Db;
use think\facade\Log;
use Throwable;

/**
 * CRMEB单商户
 * - 版本号：CRMEB-BZ v5.4.0(20240708)
 * - 使用步骤：订单表eb_store_order新增wechat_pay_profit_sharing_finish字段（默认值1，避免处理以往订单）；然后，把默认值改为0；
 */
class CrmebHelper
{
    /**
     * 获取用户的openid
     * @param int $user_id
     * @return string|null
     * @throws DataNotFoundException
     */
    public static function getUserOpenid(int $user_id): ?string
    {
        /** @var WechatUserServices $wechatServices */
        $wechatServices = app()->make(WechatUserServices::class);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userType = $userServices->value(['uid' => $user_id], 'user_type');

        $openid = $wechatServices->uidToOpenid($user_id, $userType ?: 'routine');
        if (empty($openid)) {
            throw new DataNotFoundException("用户 {$user_id} 未找到openid");
        }

        return $openid;
    }

    /**
     * 计划任务调度：自动分账
     * @return void
     */
    public static function scheduler(): void
    {
        $three_days_ago = 86400 * 2;
        $query = StoreOrder::where('paid', '=', 1)
            ->where('pay_time', '>=', time() - $three_days_ago)
            ->where('pay_type', '=', 'weixin')
            ->where('refund_status', '=', 0)
            // 订单状态（-1 : 申请退款 -2 : 退货成功 0：待发货；1：待收货；2：已收货；3：待评价；-1：已退款）
            ->whereIn('status', [2, 3])
            ->where('wechat_pay_profit_sharing_finish', '=', 0);

        $query->chunk(100, function ($orders) {
            /** @var StoreOrder $order */
            foreach ($orders as $order) {
                try {
                    CrmebHelper::autoProfitSharing($order->id);
                    $order->mark = '微信自动分账，已完成';
                    $order->save();
                } catch (Throwable $e) {
                    $order->mark = $e->getMessage();
                    $order->save();
                }
            }
        });
    }

    /**
     * 自动分账
     * @param int $id 订单表主键
     * @return void
     * @throws ErrorException
     */
    public static function autoProfitSharing(int $id): void
    {
        try {
            $storeOrder = static::verifyOrderBy($id);

            $list = static::getExecuteList($storeOrder);
            if (empty($list)) {
                $storeOrder->wechat_pay_profit_sharing_finish = 1;
                $storeOrder->save();

                // 完结分账（请求需要双向证书）
                Helper::finish($storeOrder->trade_no, $storeOrder->order_id, '该订单无需分账（无分账接收方），已完成');
            } else {
                // 查询订单待分账金额
                $_rs = Helper::orderAmountQuery($storeOrder->trade_no);
                var_dump('查询订单待分账金额【响应】', $_rs);

                $site_name = sys_config('site_name');
                $user_phone = substr_replace($storeOrder->user_phone, '****', 3, 4);

                /**
                 * 数据库事务
                 * @description 以下逻辑来自 \app\services\user\UserExtractServices::cash
                 */
                Db::transaction(function () use ($list, $storeOrder, $_rs, $site_name, $user_phone) {
                    $trade_no = $storeOrder->trade_no;
                    $order_id = $storeOrder->order_id;

                    $receivers = [];
                    foreach ($list as $item) {
                        [$uid, $brokerage] = $item;
                        $profitUser = static::verifyBrokerageByUser($uid, $brokerage);
                        if (!$profitUser) {
                            continue;
                        }

                        $mark = "用户{$user_phone}在{$site_name}消费，订单{$order_id}分账佣金";
                        $extract_price = bcmul($brokerage, '1', 2);

                        // 插入：用户提现表
                        $insertUserExtract = [
                            'uid' => $uid,
                            'real_name' => $profitUser->real_name ?: $profitUser->nickname,
                            'extract_type' => 'weixin',
                            'extract_price' => $extract_price,
                            'extract_fee' => 0,
                            'add_time' => time(),
                            'balance' => $profitUser->brokerage_price,
                            'status' => 1, // -1 未通过 0 审核中 1 已提现
                        ];
                        $userExtractModel = UserExtract::create($insertUserExtract);

                        // 更新：用户表的用户余额
                        $balance = max(bcsub((string)$profitUser->brokerage_price, $extract_price, 2), 0);
                        $profitUser->brokerage_price = $balance;
                        $profitUser->save();

                        // 插入：用户分佣账单表
                        /** @var UserBrokerageServices $userBrokerageServices */
                        $userBrokerageServices = app()->make(UserBrokerageServices::class);
                        $userBrokerageServices->income('extract', $uid, ['mark' => $mark, 'number' => $extract_price], $balance, $userExtractModel->id);

                        $openid = CrmebHelper::getUserOpenid($uid);

                        // 添加分账接收方
                        $response = Helper::api()->addReceiver(Utils::packReceiver($openid));
                        $response->toArray();
                        //var_dump($_result);

                        $receivers[] = new Receiver([
                            'type' => ReceiverTypeEnums::PERSONAL_OPENID,
                            'account' => $openid,
                            'amount' => (int)bcmul($brokerage, '100'),
                            'description' => $mark
                        ]);
                    }

                    if (empty($receivers)) {
                        $storeOrder->wechat_pay_profit_sharing_finish = 1;
                        $storeOrder->save();

                        // 完结分账（请求需要双向证书）
                        Helper::finish($trade_no, $order_id, '该订单无需分账（手动提现），已完成');
                    } else {
                        $result = Helper::singleSharing($receivers, $trade_no, $storeOrder->order_id);
                        var_dump($result);
                        $storeOrder->wechat_pay_profit_sharing_finish = 1;
                        $storeOrder->save();
                    }
                });
            }
        } catch (Throwable $throwable) {
            Log::error("【微信自动分账 订单主键：{$id}】" . $throwable->getMessage());
            throw new ErrorException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 验证订单
     * @param int $id 订单表主键
     * @return StoreOrder
     * @throws AutoProfitSharingException
     */
    protected static function verifyOrderBy(int $id): StoreOrder
    {
        /** @var StoreOrder $storeOrder */
        $storeOrder = StoreOrder::findOrEmpty($id);
        if ($storeOrder->isEmpty()) {
            throw new AutoProfitSharingException('订单不存在');
        }
        if ($storeOrder->wechat_pay_profit_sharing_finish) {
            throw new AutoProfitSharingException('当前订单分账已完结');
        }

        if ($storeOrder->refund_status) {
            throw new AutoProfitSharingException('当前订单正在申请退款或已退款，不支持分账');
        }

        $trade_no = $storeOrder->trade_no;
        if (empty($trade_no)) {
            throw new AutoProfitSharingException('微信支付订单号为空，无法分账');
        }

        if (empty($storeOrder->paid && $storeOrder->pay_time)) {
            throw new AutoProfitSharingException('订单未支付，无法分账');
        }

        // 检查开关：0=>线下手动转账；1=>自动到微信零钱
        if (sys_config('brokerage_type', 0)) {
            $storeOrder->wechat_pay_profit_sharing_finish = 1;
            $storeOrder->save();

            throw new AutoProfitSharingException('当前已设置到账方式为自动到微信零钱，为了保障资金安全，将跳过自动分账逻辑。');
        }

        return $storeOrder;
    }

    /**
     * 获取待分账的执行列表
     * @param StoreOrder $storeOrder
     * @return array
     */
    protected static function getExecuteList(StoreOrder $storeOrder): array
    {
        $list = [];
        if ($storeOrder->spread_uid && $storeOrder->one_brokerage && 1 === bccomp($storeOrder->one_brokerage, '0', 2)) {
            $list[] = [$storeOrder->spread_uid, $storeOrder->one_brokerage];
        }

        if ($storeOrder->spread_two_uid && $storeOrder->two_brokerage && 1 === bccomp($storeOrder->two_brokerage, '0', 2)) {
            $list[] = [$storeOrder->spread_two_uid, $storeOrder->two_brokerage];
        }

        return $list;
    }

    /**
     * 验证用户可提现佣金
     * @param int $uid
     * @param string $brokerage 当前待分账的金额
     * @return User|null
     */
    protected static function verifyBrokerageByUser(int $uid, string $brokerage): ?User
    {
        try {
            /** @var User $user */
            $user = User::findOrEmpty($uid);
            if ($user->isEmpty()) {
                throw new AutoProfitSharingException('用户未找到');
            }

            /** @var UserBrokerageDao $dao */
            $dao = app()->make(UserBrokerageDao::class);

            // 获取某个账户下的冻结佣金
            $broken_commission = max($dao->getUserFrozenPrice($uid), 0);
            // 用户佣金总额
            $brokerage_price = $user->brokerage_price;
            if ($brokerage_price <= 0) {
                throw new AutoProfitSharingException('用户的用户佣金总额为空。');
            }

            // 可提现佣金
            $commissionCount = bcsub((string)$brokerage_price, (string)$broken_commission, 2);
            if ($commissionCount < $brokerage) {
                throw new AutoProfitSharingException('用户的可提现佣金 小于分账金额。');
            }

            return $user;
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage() . PHP_EOL . json_encode(compact('uid', 'brokerage')));
        }

        return null;
    }

    /**
     * 添加分账接收方
     * @param int $user_id
     * @return array
     * @throws HttpException|DataNotFoundException
     */
    public static function addReceiver(int $user_id): array
    {
        $openid = self::getUserOpenid($user_id);
        return Helper::addReceiver($openid);
    }

    /**
     * 删除分账接收方
     * @param int $user_id
     * @return array
     * @throws HttpException|DataNotFoundException
     */
    public static function removeReceiver(int $user_id): array
    {
        $openid = self::getUserOpenid($user_id);
        return Helper::removeReceiver($openid);
    }
}
