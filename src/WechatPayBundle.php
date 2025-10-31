<?php

namespace WechatPayBundle;

use BaconQrCodeBundle\BaconQrCodeBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\FileStorageBundle\FileStorageBundle;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;
use Tourze\Symfony\CronJob\CronJobBundle;

class WechatPayBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            BaconQrCodeBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
            CronJobBundle::class => ['all' => true],
            FileStorageBundle::class => ['all' => true],
            RoutingAutoLoaderBundle::class => ['all' => true],
        ];
    }
}
