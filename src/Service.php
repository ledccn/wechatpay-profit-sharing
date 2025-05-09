<?php

namespace Ledc\WechatPayProfitSharing;

use think\Route;

/**
 * 系统服务
 */
class Service extends \think\Service
{
    /**
     * 绑定容器对象
     * @var array
     */
    public array $bind = [];

    /**
     * 服务注册
     * @description 通常用于注册系统服务，也就是将服务绑定到容器中。
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * 服务启动
     * @description 在所有的系统服务注册完成之后调用，用于定义启动某个系统服务之前需要做的操作。
     * @param Route $route
     * @return void
     */
    public function boot(Route $route): void
    {
        // 添加命令
        $this->commands([
            Command::class
        ]);
    }
}