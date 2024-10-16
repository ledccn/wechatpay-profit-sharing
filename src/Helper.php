<?php

namespace Ledc\WechatPayProfitSharing;

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
}
