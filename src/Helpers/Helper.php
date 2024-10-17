<?php

namespace Ledc\WechatPayProfitSharing\Helpers;

use Ledc\WechatPayProfitSharing\Config;
use Ledc\WechatPayProfitSharing\Contracts\Receiver;
use Ledc\WechatPayProfitSharing\Contracts\ReceiverTypeEnums;
use Ledc\WechatPayProfitSharing\Exceptions\HttpException;
use Ledc\WechatPayProfitSharing\ProfitService;
use Ledc\WechatPayProfitSharing\Utils;
use think\App;

/**
 * 普通直连分账，助手类
 */
class Helper
{
    /**
     * 是否启用微信分账的键名
     */
    const WECHAT_PAY_PROFIT_SHARING = 'wechat_pay_profit_sharing';

    /**
     * 是否需要分账
     * @return bool
     */
    public static function isProfitSharing(): bool
    {
        return sys_config(self::WECHAT_PAY_PROFIT_SHARING, false) || getenv(strtoupper(self::WECHAT_PAY_PROFIT_SHARING));
    }

    /**
     * 创建实例（微信支付普通直连分账）
     * - CRMEB单商户 版本号：CRMEB-BZ v5.4.0(20240708)
     * @return ProfitService
     */
    public static function api(): ProfitService
    {
        /** @var App $app */
        $app = app();
        if ($app->exists(ProfitService::class)) {
            return $app->make(ProfitService::class);
        }

        // 是否注入配置
        if ($app->exists(Config::class)) {
            $config = $app->make(Config::class);
        } else {
            $payment = [
                'mch_id' => sys_config('pay_weixin_mchid'),
                'appid' => sys_config('routine_appId') ?: sys_config('wechat_app_appid'),
                'v2_secret_key' => sys_config('pay_weixin_key'),
                'secret_key' => sys_config('pay_weixin_key_v3'),
                'certificate' => substr(public_path(parse_url(sys_config('pay_weixin_client_cert'))['path']), 0, strlen(public_path(parse_url(sys_config('pay_weixin_client_cert'))['path'])) - 1),
                'private_key' => substr(public_path(parse_url(sys_config('pay_weixin_client_key'))['path']), 0, strlen(public_path(parse_url(sys_config('pay_weixin_client_key'))['path'])) - 1),
                'serial_no' => sys_config('pay_weixin_serial_no'),
            ];
            // 实例化，绑定到容器
            $config = new Config($payment);
            $app->instance(Config::class, $config);
        }

        // 实例化，绑定到容器
        $service = new ProfitService($config);
        $app->instance(ProfitService::class, $service);

        return $service;
    }

    /**
     * 添加分账接收方
     * @param string $openid
     * @return array
     * @throws HttpException
     */
    public static function addReceiver(string $openid): array
    {
        $response = Helper::api()->addReceiver(Utils::packReceiver($openid));

        return $response->toArray();
    }

    /**
     * 删除分账接收方
     * @param string $openid
     * @return array
     * @throws HttpException
     */
    public static function removeReceiver(string $openid): array
    {
        $receiver = [
            'type' => ReceiverTypeEnums::PERSONAL_OPENID,
            'account' => $openid,
        ];

        $response = Helper::api()->removeReceiver(['receiver' => json_encode($receiver)]);

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
        $params = Utils::builderProfitSharing($data, $transaction_id, $out_order_no);

        //var_dump('请求单次分账（请求需要双向证书）【请求入参】', $params);
        $response = Helper::api()->single($params);
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
        $params = Utils::builderProfitSharing($data, $transaction_id, $out_order_no);

        $response = Helper::api()->multi($params);

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
        $response = Helper::api()->finish(['transaction_id' => $transaction_id, 'out_order_no' => $out_order_no, 'description' => $description]);
        var_dump($response);
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
        $response = Helper::api()->query(['transaction_id' => $transaction_id, 'out_order_no' => $out_order_no]);

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
        $response = Helper::api()->orderAmountQuery(['transaction_id' => $transaction_id]);

        return $response->toArray();
    }
}
