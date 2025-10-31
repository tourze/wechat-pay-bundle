<?php

namespace WechatPayBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayBundle\Entity\TradeBill;
use WechatPayBundle\Enum\BillType;

/**
 * @internal
 */
#[CoversClass(TradeBill::class)]
final class TradeBillTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new TradeBill();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'billDate' => ['billDate', new \DateTimeImmutable()],
            'billType' => ['billType', BillType::ALL],
            'hashType' => ['hashType', 'SHA256'],
            'hashValue' => ['hashValue', 'test_hash_value'],
            'downloadUrl' => ['downloadUrl', 'https://example.com/bill.csv'],
            'localFile' => ['localFile', '/tmp/bill.csv'],
        ];
    }

    private TradeBill $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = new TradeBill();
    }

    public function testEntityCreation(): void
    {
        $this->assertInstanceOf(TradeBill::class, $this->entity);
    }
}
