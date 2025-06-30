<?php

namespace WechatPayBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WechatPayBundle\DependencyInjection\WechatPayExtension;

class WechatPayExtensionTest extends TestCase
{
    private WechatPayExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new WechatPayExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad(): void
    {
        $this->extension->load([], $this->container);
        $this->assertTrue($this->container->hasDefinition('WechatPayBundle\\Service\\UnifiedOrder'));
    }
}