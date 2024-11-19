<?php

namespace Ledc\WechatPayProfitSharing\Traits;

use Ledc\WechatPayProfitSharing\HttpResponse;
use Ledc\WechatPayProfitSharing\Utils;
use Ledc\WechatPayProfitSharing\XML;

/**
 * 微信支付V2接口 POST请求
 */
trait HasPostRequestV2
{
    /**
     * 发起请求
     * @param string $url 接入点
     * @param array $params 请求参数
     * @param array $options 其他参数
     * @param bool $requiredCert 请求需要双向证书
     * @return HttpResponse
     */
    public function post(string $url, array $params, array $options = [], bool $requiredCert = false): HttpResponse
    {
        $params = array_filter($params);

        $params['sign'] = Utils::generateSign($params, $this->getConfig()->v2_secret_key);
        $body = XML::build($params);

        if (empty($options['headers'])) {
            $options['headers'] = [
                "Content-Type: text/xml; charset=utf-8",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0 Ledc/7.4",
            ];
        }

        // 请求之前回调
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        curl_setopt($curl, CURLOPT_URL, $this->getConfig()->getBaseUri() . $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if ($requiredCert) {
            curl_setopt($curl, CURLOPT_SSLCERT, $this->getConfig()->certificate);
            curl_setopt($curl, CURLOPT_SSLKEY, $this->getConfig()->private_key);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);

        $result = curl_exec($curl);
        $response = new HttpResponse($result, (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE), curl_errno($curl), curl_error($curl));
        // 请求之后回调

        curl_close($curl);

        return $response;
    }
}
