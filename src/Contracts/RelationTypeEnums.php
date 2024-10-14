<?php

namespace Ledc\WechatPayProfitSharing\Contracts;

/**
 * 与分账方的关系类型
 */
class RelationTypeEnums
{
    /**
     * 与分账方的关系类型：服务商
     */
    const SERVICE_PROVIDER = 'SERVICE_PROVIDER';
    /**
     * 与分账方的关系类型：门店
     */
    const STORE = 'STORE';
    /**
     * 与分账方的关系类型：员工
     */
    const STAFF = 'STAFF';
    /**
     * 与分账方的关系类型：店主
     */
    const STORE_OWNER = 'STORE_OWNER';
    /**
     * 与分账方的关系类型：合作伙伴
     */
    const PARTNER = 'PARTNER';
    /**
     * 与分账方的关系类型：总部
     */
    const HEADQUARTER = 'HEADQUARTER';
    /**
     * 与分账方的关系类型：品牌方
     */
    const BRAND = 'BRAND';
    /**
     * 与分账方的关系类型：分销商
     */
    const DISTRIBUTOR = 'DISTRIBUTOR';
    /**
     * 与分账方的关系类型：用户
     */
    const USER = 'USER';
    /**
     * 与分账方的关系类型：供应商
     */
    const SUPPLIER = 'SUPPLIER';
    /**
     * 与分账方的关系类型：自定义
     */
    const CUSTOM = 'CUSTOM';
}