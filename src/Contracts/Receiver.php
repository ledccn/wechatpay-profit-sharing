<?php

namespace Ledc\WechatPayProfitSharing\Contracts;

/**
 * 分账接收方，实际分账时的数据结构
 * @property string $type 分账接收方类型【MERCHANT_ID：商户号（mch_id或者sub_mch_id）；PERSONAL_OPENID：个人openid】
 * @property string $account 类型是MERCHANT_ID时，是商户号（mch_id或者sub_mch_id）；类型是PERSONAL_OPENID时，是个人openid
 * @property int $amount 分账金额，单位为分，只能为整数，不能超过原订单支付金额及最大分账比例金额
 * @property string $description 分账的原因描述，分账账单中需要体现
 */
class Receiver extends Config
{
    /**
     * 必填项
     * @var array|string[]
     */
    protected array $requiredKeys = ['type', 'account', 'amount', 'description'];
}
