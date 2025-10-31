<?php

namespace WechatPayBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use WechatPayBundle\Controller\AppController;

/**
 * @internal
 * 此测试类使用 AbstractWebTestCase，因为：
 * 1. 该测试涉及控制器功能测试，需要 Symfony 的测试环境
 * 2. 需要测试路由、依赖注入等 Web 相关功能
 * 3. 使用 AbstractWebTestCase 可以获得完整的 Symfony 测试基础设施
 */
#[CoversClass(AppController::class)]
#[RunTestsInSeparateProcesses]
final class AppControllerTest extends AbstractWebTestCase
{
    /**
     * 测试控制器可以被实例化
     */
    public function testControllerCanBeInstantiated(): void
    {
        $controller = new AppController();
        $this->assertInstanceOf(AppController::class, $controller);
    }

    /**
     * 测试控制器类的基本结构
     */
    public function testControllerClassStructure(): void
    {
        $reflection = new \ReflectionClass(AppController::class);

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
     * 测试不被允许的HTTP方法
     */
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/wechat-payment/app/pay/test-trader-no');
    }
}
