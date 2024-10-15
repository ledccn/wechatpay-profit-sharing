<?php

namespace Ledc\WechatPayProfitSharing;

use Ledc\WechatPayProfitSharing\Contracts\Receiver;
use Ledc\WechatPayProfitSharing\Contracts\ReceiverTypeEnums;
use Ledc\WechatPayProfitSharing\Contracts\RelationTypeEnums;

/**
 * 工具类
 */
class Utils
{
    /**
     * V2 签名
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

    /**
     * V3 签名
     * @param string $message
     * @param Config $config
     * @return string
     */
    public static function createSignature(string $message, Config $config): string
    {
        openssl_sign($message, $signature, openssl_pkey_get_private($config->getPrivateKey()), 'sha256WithRSAEncryption');

        return base64_encode($signature);
    }

    /**
     * 打包分账接收方的数据结构
     * @param string $openid 分账接收方账号
     * @param string $type 分账接收方类型
     * @param string $relation_type 与分账方的关系类型
     * @return array
     */
    public static function packReceiver(string $openid, string $type = ReceiverTypeEnums::PERSONAL_OPENID, string $relation_type = RelationTypeEnums::USER): array
    {
        $receiver = [
            'type' => $type,
            'relation_type' => $relation_type,
            'account' => $openid,
        ];

        return ['receiver' => json_encode($receiver)];
    }

    /**
     * 构造单次分账或多次分账的请求参数
     * @param array|Receiver[] $data 分账接收方列表
     * @param string $transaction_id 微信支付订单号
     * @param string $out_order_no 商户系统内部的分账单号，在商户系统内部唯一（单次分账、多次分账、完结分账应使用不同的商户分账单号），同一分账单号多次请求等同一次。只能是数字、大小写字母_-|*@
     * @return array
     */
    public static function builderProfitSharing(array $data, string $transaction_id, string $out_order_no): array
    {
        $receivers = [];
        foreach ($data as $receiver) {
            if ($receiver instanceof Receiver) {
                $receivers[] = $receiver->toArray();
                continue;
            }

            if (is_array($receiver) && !empty($receiver)) {
                $receivers[] = $receiver;
            }
        }

        return [
            'transaction_id' => $transaction_id,
            'out_order_no' => $out_order_no,
            'receivers' => json_encode($receivers),
        ];
    }
}
