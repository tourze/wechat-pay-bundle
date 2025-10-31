<?php

namespace WechatPayBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayBundle\Exception\InvalidTradeTypeException;
use WechatPayBundle\Exception\WechatPayException;

/**
 * @internal
 */
#[CoversClass(InvalidTradeTypeException::class)]
final class InvalidTradeTypeExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return InvalidTradeTypeException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return WechatPayException::class;
    }
}
