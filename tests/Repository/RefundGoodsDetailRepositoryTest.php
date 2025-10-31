<?php

namespace WechatPayBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\MissingIdentifierField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\RefundGoodsDetail;
use WechatPayBundle\Entity\RefundOrder;
use WechatPayBundle\Repository\RefundGoodsDetailRepository;

/**
 * @internal
 */
#[CoversClass(RefundGoodsDetailRepository::class)]
#[RunTestsInSeparateProcesses]
final class RefundGoodsDetailRepositoryTest extends AbstractRepositoryTestCase
{
    private RefundGoodsDetailRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RefundGoodsDetailRepository::class);
    }

    public function testSaveAndFlush(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId('goods_' . uniqid());
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);

        $this->repository->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(RefundGoodsDetail::class, $found);
        $this->assertEquals($entity->getMerchantGoodsId(), $found->getMerchantGoodsId());
        $this->assertEquals($entity->getUnitPrice(), $found->getUnitPrice());
        $this->assertEquals($entity->getRefundAmount(), $found->getRefundAmount());
        $this->assertEquals($entity->getRefundQuantity(), $found->getRefundQuantity());
    }

    public function testSaveWithoutFlush(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId('goods_' . uniqid());
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);

        $this->repository->save($entity, false);

        $this->assertNotNull($entity->getId());
    }

    public function testRemove(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId('goods_' . uniqid());
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);

        $this->repository->save($entity);
        $id = $entity->getId();

        $this->repository->remove($entity);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testCount(): void
    {
        $initialCount = $this->repository->count([]);

        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId('goods_' . uniqid());
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);

        $this->repository->save($entity);

        $newCount = $this->repository->count([]);
        $this->assertEquals($initialCount + 1, $newCount);
    }

    public function testCountWithCriteria(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity1 = new RefundGoodsDetail();
        $entity1->setRefundOrder($refundOrder);
        $entity1->setMerchantGoodsId('goods_1_' . uniqid());
        $entity1->setUnitPrice(500);
        $entity1->setRefundAmount(500);
        $entity1->setRefundQuantity(1);
        $this->repository->save($entity1);

        $entity2 = new RefundGoodsDetail();
        $entity2->setRefundOrder($refundOrder);
        $entity2->setMerchantGoodsId('goods_2_' . uniqid());
        $entity2->setUnitPrice(1000);
        $entity2->setRefundAmount(1000);
        $entity2->setRefundQuantity(2);
        $this->repository->save($entity2);

        $lowPriceCount = $this->repository->count(['unitPrice' => 500]);
        $highPriceCount = $this->repository->count(['unitPrice' => 1000]);

        $this->assertGreaterThanOrEqual(1, $lowPriceCount);
        $this->assertGreaterThanOrEqual(1, $highPriceCount);
    }

    public function testCountWithEmptyCriteria(): void
    {
        $count = $this->repository->count([]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFind(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId('goods_' . uniqid());
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);

        $this->repository->save($entity);
        $id = $entity->getId();

        $found = $this->repository->find($id);
        $this->assertInstanceOf(RefundGoodsDetail::class, $found);
        $this->assertEquals($id, $found->getId());
        $this->assertEquals($entity->getMerchantGoodsId(), $found->getMerchantGoodsId());
        $this->assertEquals($entity->getUnitPrice(), $found->getUnitPrice());
    }

    public function testFindWithInvalidId(): void
    {
        $found = $this->repository->find(999999);
        $this->assertNull($found);
    }

    public function testFindWithNullId(): void
    {
        // 对于RefundGoodsDetail，传入null可能会抛出异常而不是返回null
        $this->expectException(MissingIdentifierField::class);
        $this->repository->find(null);
    }

    public function testFindAll(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity1 = new RefundGoodsDetail();
        $entity1->setRefundOrder($refundOrder);
        $entity1->setMerchantGoodsId('goods_1_' . uniqid());
        $entity1->setUnitPrice(500);
        $entity1->setRefundAmount(500);
        $entity1->setRefundQuantity(1);
        $this->repository->save($entity1);

        $entity2 = new RefundGoodsDetail();
        $entity2->setRefundOrder($refundOrder);
        $entity2->setMerchantGoodsId('goods_2_' . uniqid());
        $entity2->setUnitPrice(1000);
        $entity2->setRefundAmount(1000);
        $entity2->setRefundQuantity(2);
        $this->repository->save($entity2);

        $all = $this->repository->findAll();
        $this->assertIsArray($all);
        $this->assertGreaterThanOrEqual(2, count($all));

        foreach ($all as $refundGoodsDetail) {
            $this->assertInstanceOf(RefundGoodsDetail::class, $refundGoodsDetail);
        }
    }

    public function testFindAllEmpty(): void
    {
        // 清空表
        $all = $this->repository->findAll();
        foreach ($all as $entity) {
            $this->repository->remove($entity);
        }

        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindBy(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity1 = new RefundGoodsDetail();
        $entity1->setRefundOrder($refundOrder);
        $entity1->setMerchantGoodsId('goods_1_' . uniqid());
        $entity1->setUnitPrice(500);
        $entity1->setRefundAmount(500);
        $entity1->setRefundQuantity(1);
        $this->repository->save($entity1);

        $entity2 = new RefundGoodsDetail();
        $entity2->setRefundOrder($refundOrder);
        $entity2->setMerchantGoodsId('goods_2_' . uniqid());
        $entity2->setUnitPrice(500);
        $entity2->setRefundAmount(500);
        $entity2->setRefundQuantity(1);
        $this->repository->save($entity2);

        $found = $this->repository->findBy(['unitPrice' => 500]);
        $this->assertIsArray($found);
        $this->assertCount(2, $found);

        foreach ($found as $refundGoodsDetail) {
            $this->assertInstanceOf(RefundGoodsDetail::class, $refundGoodsDetail);
            $this->assertEquals(500, $refundGoodsDetail->getUnitPrice());
        }
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entities = [];
        for ($i = 0; $i < 5; ++$i) {
            $entity = new RefundGoodsDetail();
            $entity->setRefundOrder($refundOrder);
            $entity->setMerchantGoodsId('goods_' . $i . '_' . uniqid());
            $entity->setUnitPrice(500);
            $entity->setRefundAmount(500);
            $entity->setRefundQuantity(1);
            $this->repository->save($entity);
            $entities[] = $entity;
        }

        $found = $this->repository->findBy(['unitPrice' => 500], null, 2, 1);
        $this->assertIsArray($found);
        $foundCount = count($found);
        $this->assertLessThanOrEqual(2, $foundCount);
    }

    public function testFindByEmpty(): void
    {
        $found = $this->repository->findBy(['unitPrice' => 999999]);
        $this->assertIsArray($found);
        $this->assertEmpty($found);
    }

    public function testFindOneBy(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $merchantGoodsId = 'goods_findone_' . uniqid();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId($merchantGoodsId);
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['merchantGoodsId' => $merchantGoodsId]);
        $this->assertInstanceOf(RefundGoodsDetail::class, $found);
        $this->assertEquals($merchantGoodsId, $found->getMerchantGoodsId());
        $this->assertEquals($entity->getUnitPrice(), $found->getUnitPrice());
    }

    public function testFindOneByWithOrderBy(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity1 = new RefundGoodsDetail();
        $entity1->setRefundOrder($refundOrder);
        $entity1->setMerchantGoodsId('goods_z_' . uniqid());
        $entity1->setUnitPrice(500);
        $entity1->setRefundAmount(1000);
        $entity1->setRefundQuantity(1);
        $this->repository->save($entity1);

        $entity2 = new RefundGoodsDetail();
        $entity2->setRefundOrder($refundOrder);
        $entity2->setMerchantGoodsId('goods_a_' . uniqid());
        $entity2->setUnitPrice(500);
        $entity2->setRefundAmount(500);
        $entity2->setRefundQuantity(1);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['unitPrice' => 500], ['refundAmount' => 'ASC']);
        $this->assertInstanceOf(RefundGoodsDetail::class, $found);
        $this->assertEquals($entity2->getRefundAmount(), $found->getRefundAmount());
    }

    public function testFindOneByNotFound(): void
    {
        $found = $this->repository->findOneBy(['merchantGoodsId' => 'non_existent_' . uniqid()]);
        $this->assertNull($found);
    }

    public function testFindOneByMultipleCriteria(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $merchantGoodsId = 'goods_multi_' . uniqid();
        $unitPrice = 500;
        $refundQuantity = 2;

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId($merchantGoodsId);
        $entity->setUnitPrice($unitPrice);
        $entity->setRefundAmount(1000);
        $entity->setRefundQuantity($refundQuantity);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy([
            'merchantGoodsId' => $merchantGoodsId,
            'unitPrice' => $unitPrice,
            'refundQuantity' => $refundQuantity,
            'refundAmount' => 1000,
        ]);

        $this->assertInstanceOf(RefundGoodsDetail::class, $found);
        $this->assertEquals($merchantGoodsId, $found->getMerchantGoodsId());
        $this->assertEquals($unitPrice, $found->getUnitPrice());
        $this->assertEquals($refundQuantity, $found->getRefundQuantity());
        $this->assertEquals(1000, $found->getRefundAmount());
    }

    public function testFindOneByOrderingSortsCorrectly(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(3000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity1 = new RefundGoodsDetail();
        $entity1->setRefundOrder($refundOrder);
        $entity1->setMerchantGoodsId('goods_ordering_1_' . uniqid());
        $entity1->setUnitPrice(500);
        $entity1->setRefundAmount(1500);
        $entity1->setRefundQuantity(3);
        $this->repository->save($entity1);

        $entity2 = new RefundGoodsDetail();
        $entity2->setRefundOrder($refundOrder);
        $entity2->setMerchantGoodsId('goods_ordering_2_' . uniqid());
        $entity2->setUnitPrice(500);
        $entity2->setRefundAmount(500);
        $entity2->setRefundQuantity(1);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['unitPrice' => 500], ['refundAmount' => 'ASC']);
        $this->assertInstanceOf(RefundGoodsDetail::class, $found);
        $this->assertEquals($entity2->getRefundAmount(), $found->getRefundAmount());
    }

    public function testFindOneByAssociationRefundOrderShouldReturnMatchingEntity(): void
    {
        $refundOrder1 = new RefundOrder();
        $refundOrder1->setAppId('test_app_id_1_' . uniqid());
        $refundOrder1->setMoney(1000);
        $refundOrder1->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder1);

        $refundOrder2 = new RefundOrder();
        $refundOrder2->setAppId('test_app_id_2_' . uniqid());
        $refundOrder2->setMoney(2000);
        $refundOrder2->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder2);
        self::getEntityManager()->flush();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder1);
        $entity->setMerchantGoodsId('goods_association_' . uniqid());
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['refundOrder' => $refundOrder1]);
        $this->assertInstanceOf(RefundGoodsDetail::class, $found);
        $this->assertEquals($refundOrder1, $found->getRefundOrder());
    }

    public function testCountByAssociationRefundOrderShouldReturnCorrectNumber(): void
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_id_' . uniqid());
        $refundOrder->setMoney(3000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity1 = new RefundGoodsDetail();
        $entity1->setRefundOrder($refundOrder);
        $entity1->setMerchantGoodsId('goods_count_1_' . uniqid());
        $entity1->setUnitPrice(500);
        $entity1->setRefundAmount(500);
        $entity1->setRefundQuantity(1);
        $this->repository->save($entity1);

        $entity2 = new RefundGoodsDetail();
        $entity2->setRefundOrder($refundOrder);
        $entity2->setMerchantGoodsId('goods_count_2_' . uniqid());
        $entity2->setUnitPrice(750);
        $entity2->setRefundAmount(1500);
        $entity2->setRefundQuantity(2);
        $this->repository->save($entity2);

        $entity3 = new RefundGoodsDetail();
        $entity3->setRefundOrder($refundOrder);
        $entity3->setMerchantGoodsId('goods_count_3_' . uniqid());
        $entity3->setUnitPrice(1000);
        $entity3->setRefundAmount(1000);
        $entity3->setRefundQuantity(1);
        $this->repository->save($entity3);

        $count = $this->repository->count(['refundOrder' => $refundOrder]);
        $this->assertEquals(3, $count);
    }

    protected function createNewEntity(): object
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setAppId('test_app_' . uniqid());
        $refundOrder->setMoney(1000);
        $refundOrder->setNotifyUrl('https://example.com/callback');
        self::getEntityManager()->persist($refundOrder);
        self::getEntityManager()->flush();

        $entity = new RefundGoodsDetail();
        $entity->setRefundOrder($refundOrder);
        $entity->setMerchantGoodsId('goods_' . uniqid());
        $entity->setUnitPrice(500);
        $entity->setRefundAmount(500);
        $entity->setRefundQuantity(1);

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<RefundGoodsDetail>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
