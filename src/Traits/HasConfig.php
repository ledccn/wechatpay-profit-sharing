<?php

namespace Ledc\WechatPayProfitSharing\Traits;

use Ledc\WechatPayProfitSharing\Config;

/**
 * 微信支付配置
 */
trait HasConfig
{
    /**
     * 微信支付的配置
     * @var Config
     */
    protected Config $config;

    /**
     * 【获取】微信支付的配置
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
