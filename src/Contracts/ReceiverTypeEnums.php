<?php

namespace Ledc\WechatPayProfitSharing\Contracts;

/**
 * 分账接收方类型
 */
class ReceiverTypeEnums
{
    /**
     * 分账接收方类型：商户号（mch_id或者sub_mch_id）
     */
    const MERCHANT_ID = 'MERCHANT_ID';
    /**
     * 分账接收方类型：个人openid
     */
    const PERSONAL_OPENID = 'PERSONAL_OPENID';
}
