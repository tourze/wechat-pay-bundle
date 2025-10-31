<?php

namespace WechatPayBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use WechatPayBundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // AttributeControllerLoader 测试不需要特殊的设置
    }

    public function testLoad(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->load(null);

        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    public function testSupports(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);

        $this->assertFalse($loader->supports(null, 'any_type'));
        $this->assertFalse($loader->supports('resource', 'other_type'));
    }

    public function testAutoload(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $collection);
    }
}
