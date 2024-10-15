<?php

namespace Ledc\WechatPayProfitSharing;

use InvalidArgumentException;
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
        return sys_config(self::WECHAT_PAY_PROFIT_SHARING, false);
    }

    /**
     * 创建实例（微信支付普通直连分账）
     * - CRMEB单商户 版本号：CRMEB-BZ v5.4.0(20240708)
     * @return ProfitService
     */
    public static function api(): ProfitService
    {
        if (!sys_config('pay_weixin_open', false)) {
            throw new InvalidArgumentException('微信支付未开启：pay_weixin_open');
        }

        /** @var App $app */
        $app = app();
        if ($app->exists(ProfitService::class)) {
            return $app->make(ProfitService::class);
        }

        $payment = [
            'mch_id' => sys_config('pay_weixin_mchid'),
            'appid' => sys_config('routine_appId') ?: sys_config('wechat_app_appid'),
            'v2_secret_key' => sys_config('pay_weixin_key'),
            'secret_key' => sys_config('pay_weixin_key_v3'),
            'certificate' => substr(public_path(parse_url(sys_config('pay_weixin_client_cert'))['path']), 0, strlen(public_path(parse_url(sys_config('pay_weixin_client_cert'))['path'])) - 1),
            'private_key' => substr(public_path(parse_url(sys_config('pay_weixin_client_key'))['path']), 0, strlen(public_path(parse_url(sys_config('pay_weixin_client_key'))['path'])) - 1),
            'serial_no' => sys_config('pay_weixin_serial_no'),
        ];

        // 实例化
        $config = new Config($payment);
        $service = new ProfitService($config);

        // 绑定类实例到容器
        $app->instance(Config::class, $config);
        $app->instance(ProfitService::class, $service);

        return $service;
    }
}
