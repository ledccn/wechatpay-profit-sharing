<?php

namespace Ledc\WechatPayProfitSharing;

use InvalidArgumentException;
use Phinx\Util\Util;
use RuntimeException;
use think\console\Input;
use think\console\Output;

/**
 * 安装数据库迁移文件
 */
class Command extends \think\console\Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('install:migrate:wechatpay-profit-sharing')
            ->setDescription('安装微信支付直连分账的数据库迁移文件');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        $map = [
            'UpdateStoreOrderWechatPayProfitSharingFinish' => dirname(__DIR__) . '/migrations/20250509075822.php',
        ];

        foreach ($map as $className => $templateFilepath) {
            $path = $this->migrationCreate($className, $templateFilepath);
            // 指令输出
            $output->writeln('<info>created</info> .' . str_replace(getcwd(), '', realpath($path)));
            sleep(2);
        }
    }

    /**
     * @param string $className
     * @param string $templateFilepath
     * @return string
     */
    public function migrationCreate(string $className, string $templateFilepath): string
    {
        $path = $this->ensureDirectory();

        if (!Util::isValidPhinxClassName($className)) {
            throw new InvalidArgumentException(sprintf('The migration class name "%s" is invalid. Please use CamelCase format.', $className));
        }

        if (!Util::isUniqueMigrationClassName($className, $path)) {
            throw new InvalidArgumentException(sprintf('The migration class name "%s" already exists', $className));
        }

        // Compute the file path
        $fileName = Util::mapClassNameToFileName($className);
        $filePath = $path . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($filePath)) {
            throw new InvalidArgumentException(sprintf('The file "%s" already exists', $filePath));
        }

        if (false === file_put_contents($filePath, file_get_contents($templateFilepath))) {
            throw new RuntimeException(sprintf('The file "%s" could not be written to', $path));
        }

        return $filePath;
    }

    /**
     * @return string
     */
    protected function ensureDirectory(): string
    {
        $path = $this->app->getRootPath() . 'database' . DIRECTORY_SEPARATOR . 'migrations';

        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            throw new InvalidArgumentException(sprintf('directory "%s" does not exist', $path));
        }

        if (!is_writable($path)) {
            throw new InvalidArgumentException(sprintf('directory "%s" is not writable', $path));
        }

        return $path;
    }
}