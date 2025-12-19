<?php

declare(strict_types=1);

namespace WechatPayBundle\Tests\Service\Transfer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WechatPayBundle\Service\Transfer\MerchantTransferCommand;

/**
 * @internal
 */
#[CoversClass(MerchantTransferCommand::class)]
final class MerchantTransferCommandTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-123',
            batchName: '批次名称',
            batchRemark: '批次备注',
            outDetailNo: 'detail-123',
            amount: 100,
            transferRemark: '转账备注',
            openid: 'openid-user',
            userName: '张三',
            merchantId: 'mch-123',
        );

        $this->assertSame('wx1234567890', $command->getAppId());
        $this->assertSame('batch-123', $command->getOutBatchNo());
        $this->assertSame('批次名称', $command->getBatchName());
        $this->assertSame('批次备注', $command->getBatchRemark());
        $this->assertSame('detail-123', $command->getOutDetailNo());
        $this->assertSame(100, $command->getAmount());
        $this->assertSame('转账备注', $command->getTransferRemark());
        $this->assertSame('openid-user', $command->getOpenid());
        $this->assertSame('张三', $command->getUserName());
        $this->assertSame('mch-123', $command->getMerchantId());
    }

    public function testConstructorWithRequiredParametersOnly(): void
    {
        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-123',
            batchName: '批次名称',
            batchRemark: '批次备注',
            outDetailNo: 'detail-123',
            amount: 100,
            transferRemark: '转账备注',
            openid: 'openid-user',
        );

        $this->assertSame('wx1234567890', $command->getAppId());
        $this->assertSame('batch-123', $command->getOutBatchNo());
        $this->assertSame('批次名称', $command->getBatchName());
        $this->assertSame('批次备注', $command->getBatchRemark());
        $this->assertSame('detail-123', $command->getOutDetailNo());
        $this->assertSame(100, $command->getAmount());
        $this->assertSame('转账备注', $command->getTransferRemark());
        $this->assertSame('openid-user', $command->getOpenid());
        $this->assertNull($command->getUserName());
        $this->assertNull($command->getMerchantId());
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(MerchantTransferCommand::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testGetAppId(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame('wx1234567890', $command->getAppId());
    }

    public function testGetOutBatchNo(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame('batch-123', $command->getOutBatchNo());
    }

    public function testGetBatchName(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame('批次名称', $command->getBatchName());
    }

    public function testGetBatchRemark(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame('批次备注', $command->getBatchRemark());
    }

    public function testGetOutDetailNo(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame('detail-123', $command->getOutDetailNo());
    }

    public function testGetAmount(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame(100, $command->getAmount());
    }

    public function testGetTransferRemark(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame('转账备注', $command->getTransferRemark());
    }

    public function testGetOpenid(): void
    {
        $command = $this->createMinimalCommand();

        $this->assertSame('openid-user', $command->getOpenid());
    }

    public function testGetUserName(): void
    {
        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-123',
            batchName: '批次名称',
            batchRemark: '批次备注',
            outDetailNo: 'detail-123',
            amount: 100,
            transferRemark: '转账备注',
            openid: 'openid-user',
            userName: '李四',
        );

        $this->assertSame('李四', $command->getUserName());
    }

    public function testGetMerchantId(): void
    {
        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-123',
            batchName: '批次名称',
            batchRemark: '批次备注',
            outDetailNo: 'detail-123',
            amount: 100,
            transferRemark: '转账备注',
            openid: 'openid-user',
            merchantId: 'mch-456',
        );

        $this->assertSame('mch-456', $command->getMerchantId());
    }

    private function createMinimalCommand(): MerchantTransferCommand
    {
        return new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-123',
            batchName: '批次名称',
            batchRemark: '批次备注',
            outDetailNo: 'detail-123',
            amount: 100,
            transferRemark: '转账备注',
            openid: 'openid-user',
        );
    }
}
