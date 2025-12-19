<?php

declare(strict_types=1);

namespace WechatPayBundle\Tests\Service\Transfer;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Exception\MerchantTransferException;
use WechatPayBundle\Service\Transfer\MerchantTransferCommand;
use WechatPayBundle\Service\Transfer\MerchantTransferService;
use WechatPayBundle\Service\Transfer\MerchantTransferServiceInterface;

/**
 * @internal
 */
#[CoversClass(MerchantTransferService::class)]
#[RunTestsInSeparateProcesses]
final class MerchantTransferServiceTest extends AbstractIntegrationTestCase
{
    private MerchantTransferService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(MerchantTransferService::class);
    }

    /**
     * 测试服务可以被正确实例化
     */
    public function testServiceIsInstantiable(): void
    {
        $this->assertInstanceOf(MerchantTransferService::class, $this->service);
    }

    /**
     * 测试服务实现接口
     */
    public function testServiceImplementsInterface(): void
    {
        $this->assertInstanceOf(MerchantTransferServiceInterface::class, $this->service);
    }

    /**
     * 测试类结构
     */
    public function testClassStructure(): void
    {
        $reflection = new \ReflectionClass(MerchantTransferService::class);

        // 验证类是final
        $this->assertTrue($reflection->isFinal());

        // 验证实现了接口
        $this->assertTrue($reflection->implementsInterface(MerchantTransferServiceInterface::class));

        // 验证有 transfer 方法
        $this->assertTrue($reflection->hasMethod('transfer'));

        // 验证 transfer 方法是 public 的
        $transferMethod = $reflection->getMethod('transfer');
        $this->assertTrue($transferMethod->isPublic());
    }

    /**
     * 测试商户不存在时抛出异常
     */
    public function testTransferThrowsExceptionWhenMerchantNotFound(): void
    {
        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-no-test',
            batchName: '测试提现',
            batchRemark: '测试提现备注',
            outDetailNo: 'detail-no-test',
            amount: 100,
            transferRemark: '提现测试',
            openid: 'openid-test',
            merchantId: 'nonexistent-merchant-id',
        );

        $this->expectException(MerchantTransferException::class);
        $this->expectExceptionMessage('未找到商户号');

        $this->service->transfer($command);
    }

    /**
     * 测试无可用商户时抛出异常
     */
    public function testTransferThrowsExceptionWhenNoMerchantAvailable(): void
    {
        // 清空数据库中所有商户，以确保测试隔离
        $entityManager = self::getService(EntityManagerInterface::class);
        $merchants = $entityManager->getRepository(Merchant::class)->findAll();
        foreach ($merchants as $merchant) {
            $entityManager->remove($merchant);
        }
        $entityManager->flush();

        // 不指定 merchantId，让服务尝试查找默认商户
        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-no-test',
            batchName: '测试提现',
            batchRemark: '测试提现备注',
            outDetailNo: 'detail-no-test',
            amount: 100,
            transferRemark: '提现测试',
            openid: 'openid-test',
        );

        $this->expectException(MerchantTransferException::class);
        $this->expectExceptionMessage('尚未配置任何微信商户');

        $this->service->transfer($command);
    }
}
