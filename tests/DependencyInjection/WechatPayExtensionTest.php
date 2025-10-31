<?php

namespace WechatPayBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use WechatPayBundle\DependencyInjection\WechatPayExtension;

/**
 * @internal
 */
#[CoversClass(WechatPayExtension::class)]
final class WechatPayExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
