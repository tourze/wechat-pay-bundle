<?php

declare(strict_types=1);

namespace WechatPayBundle\Tests\Service\Transfer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WechatPayBundle\Service\Transfer\MerchantTransferResult;

/**
 * @internal
 */
#[CoversClass(MerchantTransferResult::class)]
final class MerchantTransferResultTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $result = new MerchantTransferResult(
            batchId: 'batch-id-123',
            batchStatus: 'ACCEPTED',
            createTime: '2024-01-01T00:00:00+08:00',
            outBatchNo: 'batch-no-123',
            detailId: 'detail-id-123',
            detailStatus: 'SUCCESS',
            outDetailNo: 'detail-no-123',
        );

        $this->assertSame('batch-id-123', $result->getBatchId());
        $this->assertSame('ACCEPTED', $result->getBatchStatus());
        $this->assertSame('2024-01-01T00:00:00+08:00', $result->getCreateTime());
        $this->assertSame('batch-no-123', $result->getOutBatchNo());
        $this->assertSame('detail-id-123', $result->getDetailId());
        $this->assertSame('SUCCESS', $result->getDetailStatus());
        $this->assertSame('detail-no-123', $result->getOutDetailNo());
    }

    public function testConstructorWithNullableParameters(): void
    {
        $result = new MerchantTransferResult(
            batchId: 'batch-id-123',
            batchStatus: 'ACCEPTED',
            createTime: null,
            outBatchNo: 'batch-no-123',
            detailId: null,
            detailStatus: null,
            outDetailNo: 'detail-no-123',
        );

        $this->assertSame('batch-id-123', $result->getBatchId());
        $this->assertSame('ACCEPTED', $result->getBatchStatus());
        $this->assertNull($result->getCreateTime());
        $this->assertSame('batch-no-123', $result->getOutBatchNo());
        $this->assertNull($result->getDetailId());
        $this->assertNull($result->getDetailStatus());
        $this->assertSame('detail-no-123', $result->getOutDetailNo());
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(MerchantTransferResult::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testGetBatchId(): void
    {
        $result = $this->createResult();

        $this->assertSame('batch-id', $result->getBatchId());
    }

    public function testGetBatchStatus(): void
    {
        $result = $this->createResult();

        $this->assertSame('ACCEPTED', $result->getBatchStatus());
    }

    public function testGetCreateTime(): void
    {
        $result = $this->createResult();

        $this->assertSame('2024-01-01T00:00:00+08:00', $result->getCreateTime());
    }

    public function testGetOutBatchNo(): void
    {
        $result = $this->createResult();

        $this->assertSame('out-batch-no', $result->getOutBatchNo());
    }

    public function testGetDetailId(): void
    {
        $result = $this->createResult();

        $this->assertSame('detail-id', $result->getDetailId());
    }

    public function testGetDetailStatus(): void
    {
        $result = $this->createResult();

        $this->assertSame('SUCCESS', $result->getDetailStatus());
    }

    public function testGetOutDetailNo(): void
    {
        $result = $this->createResult();

        $this->assertSame('out-detail-no', $result->getOutDetailNo());
    }

    #[DataProvider('provideDetailSuccessfulCases')]
    public function testIsDetailSuccessful(?string $detailStatus, bool $expected): void
    {
        $result = new MerchantTransferResult(
            batchId: 'batch-id',
            batchStatus: 'ACCEPTED',
            createTime: null,
            outBatchNo: 'out-batch-no',
            detailId: 'detail-id',
            detailStatus: $detailStatus,
            outDetailNo: 'out-detail-no',
        );

        $this->assertSame($expected, $result->isDetailSuccessful());
    }

    /**
     * @return array<string, array{0: ?string, 1: bool}>
     */
    public static function provideDetailSuccessfulCases(): array
    {
        return [
            'SUCCESS status returns true' => ['SUCCESS', true],
            'FINISHED status returns true' => ['FINISHED', true],
            'FAIL status returns false' => ['FAIL', false],
            'PROCESSING status returns false' => ['PROCESSING', false],
            'null status returns false' => [null, false],
            'empty string returns false' => ['', false],
            'random string returns false' => ['RANDOM', false],
        ];
    }

    private function createResult(): MerchantTransferResult
    {
        return new MerchantTransferResult(
            batchId: 'batch-id',
            batchStatus: 'ACCEPTED',
            createTime: '2024-01-01T00:00:00+08:00',
            outBatchNo: 'out-batch-no',
            detailId: 'detail-id',
            detailStatus: 'SUCCESS',
            outDetailNo: 'out-detail-no',
        );
    }
}
