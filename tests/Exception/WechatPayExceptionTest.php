<?php

namespace WechatPayBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayBundle\Exception\WechatPayException;

/**
 * @internal
 */
#[CoversClass(WechatPayException::class)]
final class WechatPayExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return WechatPayException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return \RuntimeException::class;
    }
}
