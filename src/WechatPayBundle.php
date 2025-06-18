<?php

namespace WechatPayBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class WechatPayBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\Symfony\CronJob\CronJobBundle::class => ['all' => true],
        ];
    }
}
