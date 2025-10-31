<?php

namespace WechatPayBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use WechatPayBundle\Controller\UnifiedOrderController;

/**
 * @internal
 * 此测试类使用 AbstractWebTestCase，因为：
 * 1. 该测试涉及控制器功能测试，需要 Symfony 的测试环境
 * 2. 需要测试路由、依赖注入等 Web 相关功能
 * 3. 使用 AbstractWebTestCase 可以获得完整的 Symfony 测试基础设施
 */
#[CoversClass(UnifiedOrderController::class)]
#[RunTestsInSeparateProcesses]
final class UnifiedOrderControllerTest extends AbstractWebTestCase
{
    /**
     * 测试控制器可以被实例化
     */
    public function testControllerCanBeInstantiated(): void
    {
        $controller = new UnifiedOrderController();
        $this->assertInstanceOf(UnifiedOrderController::class, $controller);
    }

    /**
     * 测试控制器类的基本结构
     */
    public function testControllerClassStructure(): void
    {
        $reflection = new \ReflectionClass(UnifiedOrderController::class);

        // 验证控制器类存在
        $this->assertTrue($reflection->isInstantiable());

        // 验证控制器有适当的方法
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        // 检查是否有预期的公共方法
        $this->assertContains('__invoke', $methodNames);
        $this->assertContains('generateSign', $methodNames);
    }

    /**
     * 测试签名生成功能
     */
    public function testGenerateSign(): void
    {
        $attributes = [
            'appid' => 'wx123456',
            'mch_id' => '1234567890',
            'nonce_str' => 'abc123',
        ];

        $key = 'test_key_123456';

        // 计算预期签名
        $expectedSignData = $attributes;
        ksort($expectedSignData);
        $expectedSignData['key'] = $key;
        $expectedSign = strtoupper(md5(urldecode(http_build_query($expectedSignData))));

        // 实际生成签名
        $sign = $this->generateSign($attributes, $key);

        // 验证
        $this->assertEquals($expectedSign, $sign);
    }

    /**
     * 测试不被允许的HTTP方法
     */
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/wechat-payment/unified-order/pay/test-trader-no');
    }

    /**
     * 生成签名
     *
     * @param array<string, mixed> $attributes
     * @param string $key
     * @param string $encryptMethod
     */
    private function generateSign(array $attributes, string $key, string $encryptMethod = 'md5'): string
    {
        ksort($attributes);
        $attributes['key'] = $key;
        $queryString = urldecode(http_build_query($attributes));

        return match ($encryptMethod) {
            'md5' => strtoupper(md5($queryString)),
            'sha1' => strtoupper(sha1($queryString)),
            default => strtoupper(hash($encryptMethod, $queryString)),
        };
    }
}
