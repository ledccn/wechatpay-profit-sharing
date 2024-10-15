<?php

namespace Ledc\WechatPayProfitSharing\Helpers;

use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use Ledc\WechatPayProfitSharing\Contracts\Receiver;
use Ledc\WechatPayProfitSharing\Contracts\ReceiverTypeEnums;
use Ledc\WechatPayProfitSharing\Exceptions\HttpException;
use Ledc\WechatPayProfitSharing\Helper;
use Ledc\WechatPayProfitSharing\Utils;
use think\db\exception\DataNotFoundException;

/**
 * CRMEB单商户
 * - 版本号：CRMEB-BZ v5.4.0(20240708)
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
     * 添加分账接收方
     * @param int $user_id
     * @return array
     * @throws HttpException|DataNotFoundException
     */
    public static function addReceiver(int $user_id): array
    {
        $api = Helper::api();

        $openid = self::getUserOpenid($user_id);
        $response = $api->addReceiver(Utils::packReceiver($openid));

        return $response->toArray();
    }

    /**
     * 删除分账接收方
     * @param int $user_id
     * @return array
     * @throws HttpException|DataNotFoundException
     */
    public static function removeReceiver(int $user_id): array
    {
        $api = Helper::api();

        $openid = self::getUserOpenid($user_id);
        $receiver = [
            'type' => ReceiverTypeEnums::PERSONAL_OPENID,
            'account' => $openid,
        ];

        $response = $api->removeReceiver(['receiver' => json_encode($receiver)]);

        return $response->toArray();
    }

    /**
     * 请求单次分账（请求需要双向证书）
     * @param array|Receiver[] $data
     * @param string $transaction_id
     * @param string $out_order_no
     * @return array
     * @throws HttpException
     */
    public static function singleSharing(array $data, string $transaction_id, string $out_order_no): array
    {
        $api = Helper::api();
        $params = Utils::builderProfitSharing($data, $transaction_id, $out_order_no);

        //var_dump('请求单次分账（请求需要双向证书）【请求入参】', $params);
        $response = $api->single($params);
        //var_dump('请求单次分账（请求需要双向证书）【响应】', $response);

        return $response->toArray();
    }

    /**
     * 请求多次分账（请求需要双向证书）
     * @param array|Receiver[] $data
     * @param string $transaction_id
     * @param string $out_order_no
     * @return array
     * @throws HttpException
     */
    public static function multiSharing(array $data, string $transaction_id, string $out_order_no): array
    {
        $api = Helper::api();
        $params = Utils::builderProfitSharing($data, $transaction_id, $out_order_no);

        $response = $api->multi($params);

        return $response->toArray();
    }

    /**
     * 完结分账
     * @param string $transaction_id 微信支付订单号
     * @param string $out_order_no 商户分账单号【商户系统内部的分账单号，在商户系统内部唯一（单次分账、多次分账、完结分账应使用不同的商户分账单号），同一分账单号多次请求等同一次。只能是数字、大小写字母_-|*@】
     * @param string $description 分账完结描述
     * @return array
     * @throws HttpException
     */
    public static function finish(string $transaction_id, string $out_order_no, string $description): array
    {
        $api = Helper::api();
        $response = $api->finish(['transaction_id' => $transaction_id, 'out_order_no' => $out_order_no, 'description' => $description]);

        return $response->toArray();
    }

    /**
     * 查询分账结果
     * @param string $transaction_id 微信支付订单号
     * @param string $out_order_no 商户分账单号【查询分账结果，输入申请分账时的商户分账单号； 查询分账完结执行的结果，输入发起分账完结时的商户分账单号】
     * @return array
     * @throws HttpException
     */
    public static function query(string $transaction_id, string $out_order_no): array
    {
        $api = Helper::api();
        $response = $api->query(['transaction_id' => $transaction_id, 'out_order_no' => $out_order_no]);

        return $response->toArray();
    }

    /**
     * 查询订单待分账金额
     * @param string $transaction_id 微信支付订单号
     * @return array
     * @throws HttpException
     */
    public static function orderAmountQuery(string $transaction_id): array
    {
        $api = Helper::api();
        $response = $api->orderAmountQuery(['transaction_id' => $transaction_id]);

        return $response->toArray();
    }
}
