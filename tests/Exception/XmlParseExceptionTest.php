<?php

namespace WechatPayBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use WechatPayBundle\Exception\XmlParseException;

/**
 * @internal
 */
#[CoversClass(XmlParseException::class)]
final class XmlParseExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return XmlParseException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return \RuntimeException::class;
    }
}
