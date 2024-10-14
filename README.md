# 微信支付普通直连分账

## 安装

`composer require ledc/wechatpay-profit-sharing`

## 使用说明

开箱即用，只需要传入一个配置，初始化一个实例即可：

```php
use Ledc\WechatPayProfitSharing\Config;
use Ledc\WechatPayProfitSharing\ProfitService;

$config = [
    'mch_id' => 1360649000,
    'appid' => 1360649000,

    // 商户证书
    'private_key' => __DIR__ . '/certs/apiclient_key.pem',
    'certificate' => __DIR__ . '/certs/apiclient_cert.pem',

     // v3 API 秘钥
    'secret_key' => '43A03299A3C3FED3D8CE7B820Fxxxxx',

    // v2 API 秘钥
    'v2_secret_key' => '26db3e15cfedb44abfbb5fe94fxxxxx',

    // 平台证书：微信支付 APIv3 平台证书，需要使用工具下载
    // 下载工具：https://github.com/wechatpay-apiv3/CertificateDownloader
    'platform_certs' => [
        // 请使用绝对路径
        // '/path/to/wechatpay/cert.pem',
    ],
];

$profitService = new ProfitService(new Config($config));
```

在创建实例后，所有的方法都可以有IDE自动补全；例如：

```php
// 添加分账接收方
$profitService->addReceiver();
// 删除分账接收方
$profitService->removeReceiver();
// 请求单次分账（请求需要双向证书）
$profitService->single();
// 请求多次分账（请求需要双向证书）
$profitService->multi();
// 查询分账结果
$profitService->query();
// 完结分账（请求需要双向证书）
$profitService->finish();
// 查询订单待分账金额
$profitService->orderAmountQuery();
// 分账回退（请求需要双向证书）
$profitService->return();
// 回退结果查询
$profitService->returnQuery();
```

## 官方文档

- https://pay.weixin.qq.com/wiki/doc/api/allocation.php?chapter=26_1

## 捐赠

![reward](reward.png)
