<?php

namespace Ledc\WechatPayProfitSharing;

/**
 * 工具类
 */
class Utils
{
    /**
     * 签名
     * @param array $attributes
     * @param string $key
     * @return string
     */
    public static function generateSign(array $attributes, string $key): string
    {
        ksort($attributes);
        
        $attributes['key'] = $key;

        if (!empty($attributes['sign_type']) && $attributes['sign_type'] === 'HMAC-SHA256') {
            $signType = fn(string $message): string => hash_hmac('sha256', $message, $attributes['key']);
        } else {
            $signType = 'md5';
        }
        return strtoupper(call_user_func_array($signType, [urldecode(http_build_query($attributes))]));
    }
}
