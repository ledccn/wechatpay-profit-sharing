<?php

namespace Ledc\WechatPayProfitSharing;

/**
 * 普通直连分账，助手类
 */
class Helper
{
    /**
     * 是否启用微信分账的键名
     */
    const WECHAT_PAY_PROFIT_SHARING = 'WECHAT_PAY_PROFIT_SHARING';

    /**
     * 是否需要分账
     * @return bool
     */
    public static function isProfitSharing(): bool
    {
        // TODO... 这里应该读取数据库
        return true;
    }
}
