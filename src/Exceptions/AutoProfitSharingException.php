<?php

namespace Ledc\WechatPayProfitSharing\Exceptions;

use Exception;
use Throwable;

/**
 * HTTP请求异常
 */
class AutoProfitSharingException extends Exception
{
    const MESSAGE_PREFIX = '微信自动分账失败';

    /**
     * 构造函数
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 400, Throwable $previous = null)
    {
        $message = static::MESSAGE_PREFIX . ($message ? ' ' . $message : '');
        parent::__construct($message, $code, $previous);
    }
}
