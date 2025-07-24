<?php

namespace Ledc\WechatPayProfitSharing;

use Ledc\ThinkModelTrait\Contracts\HasMigrationCommand;
use think\console\Input;
use think\console\Output;

/**
 * 安装数据库迁移文件
 */
class Command extends \think\console\Command
{
    use HasMigrationCommand;

    /**
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('install:migrate:wechatpay-profit-sharing')
            ->setDescription('安装微信支付直连分账的数据库迁移文件');

        // 迁移文件映射
        $this->setFileMaps([
            'UpdateStoreOrderWechatPayProfitSharingFinish' => dirname(__DIR__) . '/migrations/20250509075822.php',
        ]);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        $this->eachFileMaps($input, $output);
    }
}