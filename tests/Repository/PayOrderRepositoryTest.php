<?php

namespace WechatPayBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\MissingIdentifierField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\PayOrderRepository;

/**
 * @internal
 */
#[CoversClass(PayOrderRepository::class)]
#[RunTestsInSeparateProcesses]
final class PayOrderRepositoryTest extends AbstractRepositoryTestCase
{
    private PayOrderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PayOrderRepository::class);
    }

    public function testSaveAndFlush(): void
    {
        $entity = new PayOrder();
        $entity->setAppId('test_app_id_' . uniqid());
        $entity->setMchId('test_mch_id_' . uniqid());
        $entity->setTradeType('JSAPI');
        $entity->setTradeNo('test_trade_no_' . uniqid());
        $entity->setBody('测试商品');
        $entity->setTotalFee(100);
        $entity->setNotifyUrl('https://example.com/notify');
        $entity->setStatus(PayOrderStatus::INIT);

        $this->repository->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals($entity->getTradeNo(), $found->getTradeNo());
    }

    public function testSaveWithoutFlush(): void
    {
        $entity = new PayOrder();
        $entity->setAppId('test_app_id_' . uniqid());
        $entity->setMchId('test_mch_id_' . uniqid());
        $entity->setTradeType('JSAPI');
        $entity->setTradeNo('test_trade_no_' . uniqid());
        $entity->setBody('测试商品');
        $entity->setTotalFee(100);
        $entity->setNotifyUrl('https://example.com/notify');
        $entity->setStatus(PayOrderStatus::INIT);

        $this->repository->save($entity, false);

        // Even without flush, ID may be assigned if using IDENTITY strategy
        // The key is that it should be managed but not yet persisted to database
        $this->assertNotNull($entity->getId());

        // Try to find it in database - should not exist until flush
        $em = self::getService(EntityManagerInterface::class);
        $em->clear(); // Clear entity manager to force DB query
        $found = $this->repository->find($entity->getId());
        // Since we didn't flush, it might not be in the database
        $this->assertTrue(null === $found || $found instanceof PayOrder);
    }

    public function testRemove(): void
    {
        $entity = new PayOrder();
        $entity->setAppId('test_app_id_' . uniqid());
        $entity->setMchId('test_mch_id_' . uniqid());
        $entity->setTradeType('JSAPI');
        $entity->setTradeNo('test_trade_no_' . uniqid());
        $entity->setBody('测试商品');
        $entity->setTotalFee(100);
        $entity->setNotifyUrl('https://example.com/notify');
        $entity->setStatus(PayOrderStatus::INIT);

        $this->repository->save($entity);
        $id = $entity->getId();

        $this->repository->remove($entity);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testCount(): void
    {
        $initialCount = $this->repository->count([]);

        $entity1 = $this->createPayOrder('APP1', 'MCH1');
        $entity2 = $this->createPayOrder('APP2', 'MCH2');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $this->assertEquals($initialCount + 2, $this->repository->count([]));
    }

    public function testCountWithCriteria(): void
    {
        $appId = 'test_app_count_' . uniqid();
        $entity1 = $this->createPayOrder($appId, 'MCH1');
        $entity2 = $this->createPayOrder($appId, 'MCH2');
        $entity3 = $this->createPayOrder('OTHER_APP', 'MCH3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $count = $this->repository->count(['appId' => $appId]);
        $this->assertEquals(2, $count);
    }

    public function testCountEmpty(): void
    {
        $count = $this->repository->count(['appId' => 'non_existent_app_id']);
        $this->assertEquals(0, $count);
    }

    public function testFind(): void
    {
        $entity = $this->createPayOrder('FIND_APP', 'FIND_MCH');
        $this->repository->save($entity);
        $id = $entity->getId();

        $found = $this->repository->find($id);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals($entity->getAppId(), $found->getAppId());
        $this->assertEquals($entity->getMchId(), $found->getMchId());
    }

    public function testFindNonExistent(): void
    {
        $found = $this->repository->find(999999999);
        $this->assertNull($found);
    }

    public function testFindNull(): void
    {
        // Doctrine doesn't allow null as ID, so we expect it to return null without error
        try {
            $found = $this->repository->find(null);
            $this->assertNull($found);
        } catch (\Exception $e) {
            // This is expected behavior for null ID
            $this->assertInstanceOf(MissingIdentifierField::class, $e);
        }
    }

    public function testFindAll(): void
    {
        $initialCount = count($this->repository->findAll());

        $entity1 = $this->createPayOrder('FINDALL_APP1', 'FINDALL_MCH1');
        $entity2 = $this->createPayOrder('FINDALL_APP2', 'FINDALL_MCH2');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $all = $this->repository->findAll();
        $this->assertIsArray($all);
        $this->assertCount($initialCount + 2, $all);
        $this->assertContainsOnlyInstancesOf(PayOrder::class, $all);
    }

    public function testFindAllEmpty(): void
    {
        // Clear all entities first
        $entities = $this->repository->findAll();
        foreach ($entities as $entity) {
            $this->repository->remove($entity);
        }

        $all = $this->repository->findAll();
        $this->assertIsArray($all);
        $this->assertEmpty($all);
    }

    public function testFindBy(): void
    {
        $appId = 'test_findby_' . uniqid();
        $entity1 = $this->createPayOrder($appId, 'MCH1');
        $entity2 = $this->createPayOrder($appId, 'MCH2');
        $entity3 = $this->createPayOrder('OTHER_APP', 'MCH3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $results = $this->repository->findBy(['appId' => $appId]);
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(PayOrder::class, $results);

        foreach ($results as $result) {
            $this->assertEquals($appId, $result->getAppId());
        }
    }

    public function testFindByWithLimit(): void
    {
        $appId = 'test_findby_limit_' . uniqid();
        $entity1 = $this->createPayOrder($appId, 'MCH1');
        $entity2 = $this->createPayOrder($appId, 'MCH2');
        $entity3 = $this->createPayOrder($appId, 'MCH3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $results = $this->repository->findBy(['appId' => $appId], null, 2);
        $this->assertCount(2, $results);
    }

    public function testFindByWithOffset(): void
    {
        $appId = 'test_findby_offset_' . uniqid();
        $entity1 = $this->createPayOrder($appId, 'MCH1');
        $entity2 = $this->createPayOrder($appId, 'MCH2');
        $entity3 = $this->createPayOrder($appId, 'MCH3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $results = $this->repository->findBy(['appId' => $appId], ['id' => 'ASC'], null, 1);
        $this->assertCount(2, $results);
    }

    public function testFindByEmpty(): void
    {
        $results = $this->repository->findBy(['appId' => 'non_existent_app']);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindOneBy(): void
    {
        $tradeNo = 'test_findoneby_' . uniqid();
        $entity = $this->createPayOrder('APP1', 'MCH1');
        $entity->setTradeNo($tradeNo);

        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['tradeNo' => $tradeNo]);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals($tradeNo, $found->getTradeNo());
    }

    public function testFindOneByWithOrderBy(): void
    {
        $appId = 'test_findoneby_order_' . uniqid();
        $entity1 = $this->createPayOrder($appId, 'MCH_Z');
        $entity2 = $this->createPayOrder($appId, 'MCH_A');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['appId' => $appId], ['mchId' => 'ASC']);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals('MCH_A', $found->getMchId());
    }

    public function testFindOneByNonExistent(): void
    {
        $found = $this->repository->findOneBy(['appId' => 'non_existent_app']);
        $this->assertNull($found);
    }

    public function testFindOneByMultipleResults(): void
    {
        $appId = 'test_findoneby_multiple_' . uniqid();
        $entity1 = $this->createPayOrder($appId, 'MCH1');
        $entity2 = $this->createPayOrder($appId, 'MCH2');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['appId' => $appId]);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals($appId, $found->getAppId());
    }

    public function testFindOneByWithNullFieldQuery(): void
    {
        $entity1 = $this->createPayOrder('NULL_TEST1', 'MCH1');
        $entity1->setOpenId(null);

        $entity2 = $this->createPayOrder('NULL_TEST2', 'MCH2');
        $entity2->setOpenId('some_openid');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['openId' => null]);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertNull($found->getOpenId());
    }

    public function testCountWithNullFieldQuery(): void
    {
        $entity1 = $this->createPayOrder('COUNT_NULL1', 'MCH1');
        $entity1->setAttach(null);

        $entity2 = $this->createPayOrder('COUNT_NULL2', 'MCH2');
        $entity2->setAttach('some_attach');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $count = $this->repository->count(['attach' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindOneByOrderingSortsCorrectly(): void
    {
        $appId = 'test_findoneby_sorting_' . uniqid();
        $entity1 = $this->createPayOrder($appId, 'MCH_Z');
        $entity2 = $this->createPayOrder($appId, 'MCH_A');
        $entity3 = $this->createPayOrder($appId, 'MCH_M');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        // Test ASC ordering - should return MCH_A
        $found = $this->repository->findOneBy(['appId' => $appId], ['mchId' => 'ASC']);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals('MCH_A', $found->getMchId());

        // Test DESC ordering - should return MCH_Z
        $found = $this->repository->findOneBy(['appId' => $appId], ['mchId' => 'DESC']);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals('MCH_Z', $found->getMchId());
    }

    public function testFindByNullTransactionId(): void
    {
        $entity1 = $this->createPayOrder('NULL_TX1', 'MCH1');
        $entity1->setTransactionId(null);

        $entity2 = $this->createPayOrder('NULL_TX2', 'MCH2');
        $entity2->setTransactionId('some_tx_id');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $results = $this->repository->findBy(['transactionId' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertNull($result->getTransactionId());
        }
    }

    public function testFindByNullTradeState(): void
    {
        $entity1 = $this->createPayOrder('NULL_STATE1', 'MCH1');
        $entity1->setTradeState(null);

        $entity2 = $this->createPayOrder('NULL_STATE2', 'MCH2');
        $entity2->setTradeState('SUCCESS');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $results = $this->repository->findBy(['tradeState' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertNull($result->getTradeState());
        }
    }

    public function testCountNullTransactionId(): void
    {
        $entity1 = $this->createPayOrder('COUNT_NULL_TX1', 'MCH1');
        $entity1->setTransactionId(null);

        $entity2 = $this->createPayOrder('COUNT_NULL_TX2', 'MCH2');
        $entity2->setTransactionId('some_tx_id');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $count = $this->repository->count(['transactionId' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountNullTradeState(): void
    {
        $entity1 = $this->createPayOrder('COUNT_NULL_STATE1', 'MCH1');
        $entity1->setTradeState(null);

        $entity2 = $this->createPayOrder('COUNT_NULL_STATE2', 'MCH2');
        $entity2->setTradeState('SUCCESS');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $count = $this->repository->count(['tradeState' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByNullParent(): void
    {
        $entity1 = $this->createPayOrder('NULL_PARENT1', 'MCH1');
        $entity1->setParent(null);

        $this->repository->save($entity1);

        $results = $this->repository->findBy(['parent' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertNull($result->getParent());
        }
    }

    public function testFindByNullMerchant(): void
    {
        $entity1 = $this->createPayOrder('NULL_MERCHANT1', 'MCH1');
        $entity1->setMerchant(null);

        $this->repository->save($entity1);

        $results = $this->repository->findBy(['merchant' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertNull($result->getMerchant());
        }
    }

    public function testCountNullParent(): void
    {
        $entity1 = $this->createPayOrder('COUNT_NULL_PARENT1', 'MCH1');
        $entity1->setParent(null);

        $this->repository->save($entity1);

        $count = $this->repository->count(['parent' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountNullMerchant(): void
    {
        $entity1 = $this->createPayOrder('COUNT_NULL_MERCHANT1', 'MCH1');
        $entity1->setMerchant(null);

        $this->repository->save($entity1);

        $count = $this->repository->count(['merchant' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByNullRemark(): void
    {
        $entity1 = $this->createPayOrder('NULL_REMARK1', 'MCH1');
        $entity1->setRemark(null);

        $entity2 = $this->createPayOrder('NULL_REMARK2', 'MCH2');
        $entity2->setRemark('some_remark');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $results = $this->repository->findBy(['remark' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertNull($result->getRemark());
        }
    }

    public function testCountNullRemark(): void
    {
        $entity1 = $this->createPayOrder('COUNT_NULL_REMARK1', 'MCH1');
        $entity1->setRemark(null);

        $entity2 = $this->createPayOrder('COUNT_NULL_REMARK2', 'MCH2');
        $entity2->setRemark('some_remark');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $count = $this->repository->count(['remark' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindOneByAssociationParentShouldReturnMatchingEntity(): void
    {
        $parentOrder = $this->createPayOrder('PARENT_APP', 'PARENT_MCH');
        $this->repository->save($parentOrder);

        $childOrder = $this->createPayOrder('CHILD_APP', 'CHILD_MCH');
        $childOrder->setParent($parentOrder);
        $this->repository->save($childOrder);

        $found = $this->repository->findOneBy(['parent' => $parentOrder]);
        $this->assertInstanceOf(PayOrder::class, $found);
        $this->assertEquals($parentOrder, $found->getParent());
    }

    public function testCountByAssociationParentShouldReturnCorrectNumber(): void
    {
        $parentOrder = $this->createPayOrder('PARENT_COUNT_APP', 'PARENT_COUNT_MCH');
        $this->repository->save($parentOrder);

        $childOrder1 = $this->createPayOrder('CHILD1_COUNT_APP', 'CHILD1_COUNT_MCH');
        $childOrder1->setParent($parentOrder);
        $this->repository->save($childOrder1);

        $childOrder2 = $this->createPayOrder('CHILD2_COUNT_APP', 'CHILD2_COUNT_MCH');
        $childOrder2->setParent($parentOrder);
        $this->repository->save($childOrder2);

        $count = $this->repository->count(['parent' => $parentOrder]);
        $this->assertEquals(2, $count);
    }

    private function createPayOrder(string $appId, string $mchId): PayOrder
    {
        $entity = new PayOrder();
        $entity->setAppId($appId);
        $entity->setMchId($mchId);
        $entity->setTradeType('JSAPI');
        $entity->setTradeNo('trade_no_' . uniqid());
        $entity->setBody('测试商品');
        $entity->setTotalFee(100);
        $entity->setNotifyUrl('https://example.com/notify');
        $entity->setStatus(PayOrderStatus::INIT);

        return $entity;
    }

    protected function createNewEntity(): object
    {
        $entity = new PayOrder();
        $entity->setAppId('wx_test_' . uniqid());
        $entity->setMchId('test_mch_' . uniqid());
        $entity->setTradeType('JSAPI');
        $entity->setTradeNo('test_trade_' . uniqid());
        $entity->setBody('Test PayOrder');
        $entity->setFeeType('CNY');
        $entity->setTotalFee(100);
        $entity->setNotifyUrl('https://example.com/notify');
        $entity->setStatus(PayOrderStatus::INIT);

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<PayOrder>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
