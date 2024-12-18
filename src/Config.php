<?php

namespace Ledc\WechatPayProfitSharing;

use Ledc\WechatPayProfitSharing\Contracts\Config as ConfigContract;
use LogicException;

/**
 * 微信支付普通直连分账配置
 * @property string $mch_id 商户号
 * @property string $appid 公众账号ID
 * @property string $v2_secret_key 商户API v2密钥
 * @property string $secret_key 商户API v3密钥
 * @property string $certificate 商户的微信支付证书，文件名 apiclient_cert.pem
 * @property string $private_key 商户的微信支付证书密钥，文件名 apiclient_key.pem
 * @property string $base_uri 微信支付接口地址uri
 */
class Config extends ConfigContract
{
    /**
     * 必填项
     * @var array|string[]
     */
    protected array $requiredKeys = ['mch_id', 'appid', 'v2_secret_key', 'secret_key', 'certificate', 'private_key'];

    /**
     * 【获取】商户的微信支付证书密钥
     * - openssl_pkey_get_private
     * @return string
     */
    public function getPrivateKey(): string
    {
        if (!is_file($this->private_key)) {
            throw new LogicException("商户的微信支付证书密钥不存在");
        }
        return trim(file_get_contents($this->private_key));
    }

    /**
     * 【获取】商户的微信支付证书
     * - openssl_pkey_get_public
     * @return string
     */
    public function getPublicKey(): string
    {
        if (!is_file($this->certificate)) {
            throw new LogicException('商户的微信支付证书不存在');
        }
        return trim(file_get_contents($this->certificate));
    }

    /**
     * 获取序列号：商户的微信支付证书
     * @throws LogicException
     */
    public function getSerialNo(): string
    {
        $info = openssl_x509_parse($this->getPublicKey());

        if ($info === false || !isset($info['serialNumberHex'])) {
            throw new LogicException('Read the $certificate failed, please check it whether or nor correct');
        }

        return strtoupper($info['serialNumberHex']);
    }

    /**
     * 【获取】微信支付接口地址uri
     * @return string
     */
    public function getBaseUri(): string
    {
        return rtrim($this->base_uri ?: 'https://api.mch.weixin.qq.com/', '/') . '/';
    }
}
