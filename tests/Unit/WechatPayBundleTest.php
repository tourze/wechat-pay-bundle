<?php

namespace WechatPayBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\WechatPayBundle;

class WechatPayBundleTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new WechatPayBundle();
        $this->assertInstanceOf(WechatPayBundle::class, $bundle);
    }
}