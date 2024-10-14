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
     * @param string $encryptMethod
     * @return string
     */
    public static function generateSign(array $attributes, string $key, string $encryptMethod = 'md5'): string
    {
        // 集合M内非空参数值的参数按照参数名ASCII码从小到大排序（字典序）
        ksort($attributes);

        $attributes['key'] = $key;

        return strtoupper(call_user_func_array($encryptMethod, [urldecode(http_build_query($attributes))]));
    }
}
