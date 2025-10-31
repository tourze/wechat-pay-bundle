<?php

declare(strict_types=1);

namespace WechatPayBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use WechatPayBundle\WechatPayBundle;

/**
 * @internal
 */
#[CoversClass(WechatPayBundle::class)]
#[RunTestsInSeparateProcesses]
final class WechatPayBundleTest extends AbstractBundleTestCase
{
}
