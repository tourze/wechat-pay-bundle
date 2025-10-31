<?php

namespace WechatPayBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayBundle\Entity\FundFlowBill;
use WechatPayBundle\Enum\AccountType;

/**
 * @internal
 */
#[CoversClass(FundFlowBill::class)]
final class FundFlowBillTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new FundFlowBill();
    }

    public function testEntity(): void
    {
        $entity = new FundFlowBill();
        $this->assertInstanceOf(FundFlowBill::class, $entity);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'billDate' => ['billDate', new \DateTimeImmutable('2024-01-01')],
            'accountType' => ['accountType', AccountType::BASIC],
            'hashType' => ['hashType', 'SHA256'],
            'hashValue' => ['hashValue', 'abc123hash'],
            'downloadUrl' => ['downloadUrl', 'https://example.com/download'],
            'localFile' => ['localFile', '/path/to/local/file.csv'],
        ];
    }
}
