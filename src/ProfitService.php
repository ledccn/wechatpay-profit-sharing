<?php

namespace Ledc\WechatPayProfitSharing;

use Ledc\WechatPayProfitSharing\Traits\HasConfig;
use Ledc\WechatPayProfitSharing\Traits\HasPostRequestV2;

/**
 * 微信支付普通直连分账
 * @link https://pay.weixin.qq.com/wiki/doc/api/allocation.php?chapter=26_1
 * @description 实现分账只是在普通支付下单接口中新增了一个分账参数profit_sharing，其他与普通支付方式完全相同。目前支持付款码支付、JSAPI支付、Native支付、APP支付、小程序支付、H5支付、委托代扣、车主平台。
 */
class ProfitService
{
    use HasConfig, HasPostRequestV2;

    /**
     * 构造函数
     * @param Config $config 普通直连分账配置
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 添加分账接收方
     * - 商户发起添加分账接收方请求，后续可通过发起分账请求将结算后的钱分到该分账接收方。
     * @param array $params
     * @return HttpResponse
     */
    public function addReceiver(array $params): HttpResponse
    {
        $params['appid'] = $this->getConfig()->appid;
        return $this->request('POST', 'pay/profitsharingaddreceiver', $params);
    }

    /**
     * 删除分账接收方
     * - 商户发起删除分账接收方请求，删除后不支持将结算后的钱分到该分账接收方。
     * @param array $params
     * @return HttpResponse
     */
    public function removeReceiver(array $params): HttpResponse
    {
        $params['appid'] = $this->getConfig()->appid;
        return $this->request('POST', 'pay/profitsharingremovereceiver', $params);
    }

    /**
     * 请求单次分账（请求需要双向证书）
     * - 单次分账请求按照传入的分账接收方账号和资金进行分账，同时会将订单剩余的待分账金额解冻给本商户。故操作成功后，订单不能再进行分账，也不能进行分账完结。
     * @param array $params
     * @return HttpResponse
     */
    public function single(array $params): HttpResponse
    {
        $params['appid'] = $this->getConfig()->appid;
        return $this->request('POST', 'secapi/pay/profitsharing', $params, [], true);
    }

    /**
     * 请求多次分账（请求需要双向证书）
     * - 微信订单支付成功后，商户发起分账请求，将结算后的钱分到分账接收方。多次分账请求仅会按照传入的分账接收方进行分账，不会对剩余的金额进行任何操作。故操作成功后，在待分账金额不等于零时，订单依旧能够再次进行分账。
     * - 多次分账，可以将本商户作为分账接收方直接传入，实现释放资金给本商户的功能
     * @param array $params
     * @return HttpResponse
     */
    public function multi(array $params): HttpResponse
    {
        $params['appid'] = $this->getConfig()->appid;
        return $this->request('POST', 'secapi/pay/multiprofitsharing', $params, [], true);
    }

    /**
     * 查询分账结果
     * - 发起分账请求后，可调用此接口查询分账结果；发起分账完结请求后，可调用此接口查询分账完结的执行结果。
     * - 接口频率：80QPS
     * @param array $params
     * @return HttpResponse
     */
    public function query(array $params): HttpResponse
    {
        return $this->request('POST', 'pay/profitsharingquery', $params);
    }

    /**
     * 完结分账（请求需要双向证书）
     * - 不需要进行分账的订单，可直接调用本接口将订单的金额全部解冻给本商户.
     * - 调用多次分账接口后，需要解冻剩余资金时，调用本接口将剩余的分账金额全部解冻给本商户
     * - 已调用请求单次分账后，剩余待分账金额为零，不需要再调用此接口。
     * @param array $params
     * @return HttpResponse
     */
    public function finish(array $params): HttpResponse
    {
        $params['appid'] = $this->getConfig()->appid;
        return $this->request('POST', 'pay/profitsharingfinish', $params, [], true);
    }

    /**
     * 查询订单待分账金额
     * - 商户可通过调用此接口查询订单剩余待分金额。
     * - 接口频率：30QPS
     * @param array $params
     * @return HttpResponse
     */
    public function orderAmountQuery(array $params): HttpResponse
    {
        return $this->request('POST', 'pay/profitsharingorderamountquery', $params);
    }

    /**
     * 分账回退（请求需要双向证书）
     * - 此功能需要接收方在商户平台-交易中心-分账-分账接收设置下，开启同意分账回退后，才能使用。
     * - 对订单进行退款时，如果订单已经分账，可以先调用此接口将指定的金额从分账接收方（仅限商户类型的分账接收方）回退给本商户，然后再退款。
     * - 回退以原分账请求为依据，可以对分给分账接收方的金额进行多次回退，只要满足累计回退不超过该请求中分给接收方的金额。
     * - 此接口采用同步处理模式，即在接收到商户请求后，会实时返回处理结果
     * - 分账回退的时限是180天
     * - 接口频率：30QPS
     * @param array $params
     * @return HttpResponse
     */
    public function return(array $params): HttpResponse
    {
        $params['appid'] = $this->getConfig()->appid;
        return $this->request('POST', 'secapi/pay/profitsharingreturn', $params, [], true);
    }

    /**
     * 回退结果查询
     * - 商户需要核实回退结果，可调用此接口查询回退结果。
     * - 如果分账回退接口返回状态为处理中，可调用此接口查询回退结果
     * - 接口频率：30QPS
     * @param array $params
     * @return HttpResponse
     */
    public function returnQuery(array $params): HttpResponse
    {
        $params['appid'] = $this->getConfig()->appid;
        return $this->request('POST', 'pay/profitsharingreturnquery', $params);
    }

    /**
     * 发起请求
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $options
     * @param bool $requiredCert 请求需要双向证书
     * @return HttpResponse
     */
    public function request(string $method, string $url, array $params, array $options = [], bool $requiredCert = false): HttpResponse
    {
        $params['mch_id'] = $this->getConfig()->mch_id;
        $params['nonce_str'] = uniqid();
        $params['sign_type'] = 'HMAC-SHA256';
        return $this->post($url, $params, $options, $requiredCert);
    }
}
