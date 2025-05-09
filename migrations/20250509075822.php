<?php

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

/**
 * 订单表添加字段：微信支付分账完成
 */
class UpdateStoreOrderWechatPayProfitSharingFinish extends Migrator
{
    /**
     * Change Method.
     */
    public function change()
    {
        // 添加字段
        $table = $this->table("store_order");
        $table->addColumn('wechat_pay_profit_sharing_finish', AdapterInterface::PHINX_TYPE_INTEGER, ['limit' => MysqlAdapter::INT_TINY, 'comment' => '微信支付分账完成', 'null' => false, 'default' => 0, 'signed' => false])
            ->addIndex('wechat_pay_profit_sharing_finish')
            ->update();

        // 添加数据：微信支付分账开关
        $row = $this->fetchRow("SELECT * FROM `eb_system_config` WHERE `menu_name` = 'pay_weixin_mchid'");
        if (!empty($row)) {
            $config_tab_id = $row['config_tab_id'];
            $table = $this->table('system_config');
            $table->insert([
                'menu_name' => 'wechat_pay_profit_sharing',
                'type' => 'switch',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 0,
                'high' => 0,
                'info' => '启用微信直连分账',
                'desc' => '订单确认收货后，按照一定的周期，自动将订单佣金分账给分销员',
                'status' => 1,
            ]);
            $table->saveData();
        }
    }
}
