<?php

namespace Ledc\WechatPayProfitSharing;

use JsonSerializable;
use Ledc\WechatPayProfitSharing\Exceptions\HttpException;

/**
 * HTTP响应
 */
class HttpResponse implements JsonSerializable
{
    /**
     * 响应
     * @var bool|string
     */
    protected $response;
    /**
     * 响应代码
     * @var int
     */
    protected int $statusCode = 0;
    /**
     * 返回最后一次的错误代码，错误代码或在没有错误发生时返回 0 (零)
     * @var int
     */
    protected int $curlErrorNo = 0;
    /**
     * 返回错误信息，或者如果没有任何错误发生就返回 '' (空字符串)。
     * @var string
     */
    protected string $curlErrorMessage = '';

    /**
     * 构造函数
     * @param bool|string $response
     * @param int $statusCode
     * @param int $curlErrorNo
     * @param string $curlErrorMessage
     */
    public function __construct($response, int $statusCode, int $curlErrorNo, string $curlErrorMessage)
    {
        $this->response = $response;
        $this->statusCode = $statusCode;
        $this->curlErrorNo = $curlErrorNo;
        $this->curlErrorMessage = $curlErrorMessage;
    }

    /**
     * 获取响应体
     * @return bool|string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * 请求结果转数组
     * @return array
     * @throws HttpException
     */
    public function toArray(): array
    {
        if ($this->isFailed()) {
            throw new HttpException();
        }

        if (empty($this->getResponse()) || false === is_string($this->getResponse())) {
            throw new HttpException('响应为空');
        }

        $result = XML::parse($this->response);
        $return_code = $result["return_code"] ?? null;
        $result_code = $result["result_code"] ?? null;
        if ('SUCCESS' !== $return_code || 'SUCCESS' !== $result_code) {
            $err_code = $result["err_code"] ?? '';
            $err_code_des = $result["err_code_des"] ?? '';
            throw new HttpException('[' . $err_code . '] ' . $err_code_des);
        }

        // TODO... 验签

        return $result;
    }

    /**
     * 获取响应的HTTP状态码
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 获取错误代码
     * - 在没有错误发生时返回 0 (零)
     * @return int
     */
    public function getCurlErrorNo(): int
    {
        return $this->curlErrorNo;
    }

    /**
     * 获取错误信息
     * - 没有任何错误发生就返回 '' (空字符串)。
     * @return string
     */
    public function getCurlErrorMessage(): string
    {
        return $this->curlErrorMessage;
    }

    /**
     * 判断响应成功
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return 200 <= $this->getStatusCode() && $this->getStatusCode() < 300;
    }

    /**
     * 判断响应失败啦
     * @return bool
     */
    public function isFailed(): bool
    {
        return !$this->isSuccessful();
    }

    /**
     * 转数组
     * @return array
     * @throws HttpException
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 转JSON
     * @param int $options
     * @return string
     * @throws HttpException
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * 转为字符串
     * @return string
     * @throws HttpException
     */
    public function __toString(): string
    {
        return $this->toJson(JSON_UNESCAPED_UNICODE);
    }
}
