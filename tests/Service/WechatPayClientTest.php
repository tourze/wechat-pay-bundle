<?php

namespace WechatPayBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use WechatPayBundle\Service\WechatPayClient;

/**
 * @internal
 * 此测试类使用 TestCase 而不是 AbstractIntegrationTestCase，因为：
 * 1. WechatPayClient 是一个简单的包装类，不需要容器依赖
 * 2. 使用 Mock HTTP Client 即可完成测试
 * 3. 使用 TestCase 更加轻量化和高效
 */
#[CoversClass(WechatPayClient::class)]
final class WechatPayClientTest extends TestCase
{
    /**
     * 测试服务实例化
     */
    public function testServiceInstantiation(): void
    {
        $mockHttpClient = new MockHttpClient();
        $wechatPayClient = new WechatPayClient($mockHttpClient);

        $this->assertInstanceOf(WechatPayClient::class, $wechatPayClient);
    }

    /**
     * 测试获取HTTP客户端
     */
    public function testGetClient(): void
    {
        $mockHttpClient = new MockHttpClient();
        $wechatPayClient = new WechatPayClient($mockHttpClient);

        $client = $wechatPayClient->getClient();

        $this->assertSame($mockHttpClient, $client);
    }
}
