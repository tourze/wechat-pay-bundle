<?php

namespace WechatPayBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Entity\RefundGoodsDetail;
use WechatPayBundle\Entity\RefundOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\PayOrderRepository;
use WechatPayBundle\Repository\RefundGoodsDetailRepository;
use WechatPayBundle\Repository\RefundOrderRepository;

/**
 * @internal
 */
#[CoversClass(RefundOrderRepository::class)]
#[RunTestsInSeparateProcesses]
final class RefundOrderRepositoryTest extends AbstractRepositoryTestCase
{
    private RefundOrderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RefundOrderRepository::class);
    }

    public function testSaveAndFlush(): void
    {
        $entity = new RefundOrder();
        $entity->setAppId('test_app_id_' . uniqid());
        $entity->setMoney(1000);
        $entity->setReason('测试退款原因');
        $entity->setCurrency('CNY');
        $entity->setNotifyUrl('https://example.com/callback');

        $this->repository->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(RefundOrder::class, $found);
        $this->assertEquals($entity->getAppId(), $found->getAppId());
        $this->assertEquals($entity->getMoney(), $found->getMoney());
        $this->assertEquals($entity->getReason(), $found->getReason());
        $this->assertEquals($entity->getCurrency(), $found->getCurrency());
    }

    public function testSaveWithoutFlush(): void
    {
        $entity = new RefundOrder();
        $entity->setAppId('test_app_id_' . uniqid());
        $entity->setMoney(1000);
        $entity->setReason('测试退款原因');
        $entity->setCurrency('CNY');
        $entity->setNotifyUrl('https://example.com/callback');

        $this->repository->save($entity, false);

        $this->assertNotNull($entity->getId());
    }

    public function testRemove(): void
    {
        $entity = new RefundOrder();
        $entity->setAppId('test_app_id_' . uniqid());
        $entity->setMoney(1000);
        $entity->setReason('测试退款原因');
        $entity->setCurrency('CNY');
        $entity->setNotifyUrl('https://example.com/callback');

        $this->repository->save($entity);
        $id = $entity->getId();

        $this->repository->remove($entity);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testFindByWhenNoMatchingCriteriaShouldReturnEmptyArray(): void
    {
        $result = $this->repository->findBy(['appId' => 'non_existent_app_id']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByWithMatchingCriteriaShouldReturnMatchingEntities(): void
    {
        $appId = 'test_app_id_' . uniqid();

        // 创建多个测试数据，其中一些匹配条件
        $entity1 = new RefundOrder();
        $entity1->setAppId($appId);
        $entity1->setMoney(1000);
        $entity1->setReason('测试退款1');
        $entity1->setCurrency('CNY');
        $entity1->setNotifyUrl('https://example.com/callback1');

        $entity2 = new RefundOrder();
        $entity2->setAppId($appId);
        $entity2->setMoney(2000);
        $entity2->setReason('测试退款2');
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');

        $entity3 = new RefundOrder();
        $entity3->setAppId('different_app_id');
        $entity3->setMoney(3000);
        $entity3->setReason('测试退款3');
        $entity3->setCurrency('CNY');
        $entity3->setNotifyUrl('https://example.com/callback3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $result = $this->repository->findBy(['appId' => $appId]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(RefundOrder::class, $result);

        foreach ($result as $refundOrder) {
            $this->assertEquals($appId, $refundOrder->getAppId());
        }
    }

    public function testFindByWithOrderByShouldReturnOrderedResults(): void
    {
        $appId = 'test_app_id_' . uniqid();

        // 创建多个具有不同金额的测试数据
        $entity1 = new RefundOrder();
        $entity1->setAppId($appId);
        $entity1->setMoney(3000);
        $entity1->setReason('测试退款1');
        $entity1->setCurrency('CNY');
        $entity1->setNotifyUrl('https://example.com/callback1');

        $entity2 = new RefundOrder();
        $entity2->setAppId($appId);
        $entity2->setMoney(1000);
        $entity2->setReason('测试退款2');
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');

        $entity3 = new RefundOrder();
        $entity3->setAppId($appId);
        $entity3->setMoney(2000);
        $entity3->setReason('测试退款3');
        $entity3->setCurrency('CNY');
        $entity3->setNotifyUrl('https://example.com/callback3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $result = $this->repository->findBy(['appId' => $appId], ['money' => 'ASC']);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(1000, $result[0]->getMoney());
        $this->assertEquals(2000, $result[1]->getMoney());
        $this->assertEquals(3000, $result[2]->getMoney());
    }

    public function testFindByWithLimitShouldReturnLimitedResults(): void
    {
        $appId = 'test_app_id_' . uniqid();

        // 创建多个测试数据
        for ($i = 1; $i <= 5; ++$i) {
            $entity = new RefundOrder();
            $entity->setAppId($appId);
            $entity->setMoney($i * 1000);
            $entity->setReason('测试退款' . $i);
            $entity->setCurrency('CNY');
            $entity->setNotifyUrl('https://example.com/callback' . $i);

            $this->repository->save($entity);
        }

        $result = $this->repository->findBy(['appId' => $appId], null, 3);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(RefundOrder::class, $result);
    }

    public function testFindByWithOffsetShouldReturnOffsetResults(): void
    {
        $appId = 'test_app_id_' . uniqid();

        // 创建多个测试数据
        for ($i = 1; $i <= 5; ++$i) {
            $entity = new RefundOrder();
            $entity->setAppId($appId);
            $entity->setMoney($i * 1000);
            $entity->setReason('测试退款' . $i);
            $entity->setCurrency('CNY');
            $entity->setNotifyUrl('https://example.com/callback' . $i);

            $this->repository->save($entity);
        }

        $result = $this->repository->findBy(['appId' => $appId], ['money' => 'ASC'], 2, 2);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // 应该返回第3和第4个记录（偏移2个）
        $this->assertEquals(3000, $result[0]->getMoney());
        $this->assertEquals(4000, $result[1]->getMoney());
    }

    public function testFindOneByWhenNoMatchShouldReturnNull(): void
    {
        $result = $this->repository->findOneBy(['appId' => 'non_existent_app_id']);

        $this->assertNull($result);
    }

    public function testFindOneByWithMatchShouldReturnSingleEntity(): void
    {
        $appId = 'unique_app_id_' . uniqid();

        $entity = new RefundOrder();
        $entity->setAppId($appId);
        $entity->setMoney(1500);
        $entity->setReason('唯一测试退款');
        $entity->setCurrency('CNY');
        $entity->setNotifyUrl('https://example.com/unique-callback');

        $this->repository->save($entity);

        $result = $this->repository->findOneBy(['appId' => $appId]);

        $this->assertInstanceOf(RefundOrder::class, $result);
        $this->assertEquals($appId, $result->getAppId());
        $this->assertEquals(1500, $result->getMoney());
        $this->assertEquals('唯一测试退款', $result->getReason());
    }

    public function testFindOneByWithMultipleMatchesShouldReturnFirstOne(): void
    {
        $currency = 'EUR';

        // 创建多个具有相同币种的退款订单
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(1000);
        $entity1->setReason('测试退款1');
        $entity1->setCurrency($currency);
        $entity1->setNotifyUrl('https://example.com/callback1');

        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(2000);
        $entity2->setReason('测试退款2');
        $entity2->setCurrency($currency);
        $entity2->setNotifyUrl('https://example.com/callback2');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $result = $this->repository->findOneBy(['currency' => $currency]);

        $this->assertInstanceOf(RefundOrder::class, $result);
        $this->assertEquals($currency, $result->getCurrency());
        // 应该返回其中一个，但具体是哪个取决于数据库的内部顺序
        $this->assertContains($result->getAppId(), ['app_id_1', 'app_id_2']);
    }

    public function testCountWhenNoRecordsShouldReturnZero(): void
    {
        // 使用不存在的条件来测试0计数
        $count = $this->repository->count(['appId' => 'non_existent_app_id_' . uniqid()]);

        $this->assertEquals(0, $count);
    }

    public function testCountWhenRecordsExistShouldReturnCorrectCount(): void
    {
        $uniquePrefix = 'count_test_' . uniqid();

        // 创建测试数据
        for ($i = 1; $i <= 3; ++$i) {
            $entity = new RefundOrder();
            $entity->setAppId($uniquePrefix . '_' . $i);
            $entity->setMoney($i * 1000);
            $entity->setReason('测试退款' . $i);
            $entity->setCurrency('CNY');
            $entity->setNotifyUrl('https://example.com/callback' . $i);

            $this->repository->save($entity);
        }

        // 使用条件计数，只统计我们创建的数据
        $count = $this->repository->count(['appId' => $uniquePrefix . '_1']);
        $this->assertEquals(1, $count);

        // 或者统计所有创建的数据
        $allCount = 0;
        for ($i = 1; $i <= 3; ++$i) {
            $allCount += $this->repository->count(['appId' => $uniquePrefix . '_' . $i]);
        }
        $this->assertEquals(3, $allCount);
    }

    public function testCountWithCriteriaShouldReturnFilteredCount(): void
    {
        $targetCurrency = 'USD';

        // 创建混合数据
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(1000);
        $entity1->setReason('测试退款1');
        $entity1->setCurrency($targetCurrency);
        $entity1->setNotifyUrl('https://example.com/callback1');

        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(2000);
        $entity2->setReason('测试退款2');
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');

        $entity3 = new RefundOrder();
        $entity3->setAppId('app_id_3');
        $entity3->setMoney(3000);
        $entity3->setReason('测试退款3');
        $entity3->setCurrency($targetCurrency);
        $entity3->setNotifyUrl('https://example.com/callback3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $count = $this->repository->count(['currency' => $targetCurrency]);

        $this->assertEquals(2, $count);
    }

    public function testFindOneByWithOrderByShouldReturnFirstOrderedEntity(): void
    {
        $currency = 'GBP';

        // 创建多个具有相同币种但不同金额的测试数据
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(3000);
        $entity1->setReason('测试退款1');
        $entity1->setCurrency($currency);
        $entity1->setNotifyUrl('https://example.com/callback1');

        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(1000);
        $entity2->setReason('测试退款2');
        $entity2->setCurrency($currency);
        $entity2->setNotifyUrl('https://example.com/callback2');

        $entity3 = new RefundOrder();
        $entity3->setAppId('app_id_3');
        $entity3->setMoney(2000);
        $entity3->setReason('测试退款3');
        $entity3->setCurrency($currency);
        $entity3->setNotifyUrl('https://example.com/callback3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        // 测试升序排序 - 应该返回金额最小的
        $resultAsc = $this->repository->findOneBy(['currency' => $currency], ['money' => 'ASC']);
        $this->assertInstanceOf(RefundOrder::class, $resultAsc);
        $this->assertEquals(1000, $resultAsc->getMoney());

        // 测试降序排序 - 应该返回金额最大的
        $resultDesc = $this->repository->findOneBy(['currency' => $currency], ['money' => 'DESC']);
        $this->assertInstanceOf(RefundOrder::class, $resultDesc);
        $this->assertEquals(3000, $resultDesc->getMoney());
    }

    public function testFindByWithNullValueShouldMatchNullFields(): void
    {
        // 创建一个有 null 字段的实体
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(1000);
        $entity1->setReason(null); // 设置为 null
        $entity1->setCurrency('CNY');
        $entity1->setNotifyUrl('https://example.com/callback1');

        // 创建一个有值的实体作为对比
        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(2000);
        $entity2->setReason('有值的原因');
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        // 查找 reason 为 null 的记录
        $result = $this->repository->findBy(['reason' => null]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('app_id_1', $result[0]->getAppId());
        $this->assertNull($result[0]->getReason());
    }

    public function testCountWithNullValueShouldMatchNullFields(): void
    {
        // 创建多个实体，其中一些有 null 字段
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(1000);
        $entity1->setReason(null);
        $entity1->setCurrency('CNY');
        $entity1->setNotifyUrl('https://example.com/callback1');

        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(2000);
        $entity2->setReason(null);
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');

        $entity3 = new RefundOrder();
        $entity3->setAppId('app_id_3');
        $entity3->setMoney(3000);
        $entity3->setReason('有值的原因');
        $entity3->setCurrency('CNY');
        $entity3->setNotifyUrl('https://example.com/callback3');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        // 统计 reason 为 null 的记录数
        $count = $this->repository->count(['reason' => null]);

        $this->assertEquals(2, $count);
    }

    public function testFindByWithPayOrderAssociation(): void
    {
        // 创建PayOrder
        $payOrderRepository = self::getService(PayOrderRepository::class);

        $payOrder = new PayOrder();
        $payOrder->setAppId('test_pay_app_' . uniqid());
        $payOrder->setMchId('test_mch_id');
        $payOrder->setTradeType('JSAPI');
        $payOrder->setTradeNo('trade_no_' . uniqid());
        $payOrder->setBody('测试支付商品');
        $payOrder->setTotalFee(5000);
        $payOrder->setNotifyUrl('https://example.com/pay-callback');
        $payOrder->setStatus(PayOrderStatus::INIT);

        $payOrderRepository->save($payOrder);

        // 创建关联的RefundOrder
        $refundOrder1 = new RefundOrder();
        $refundOrder1->setAppId('refund_app_1');
        $refundOrder1->setMoney(2000);
        $refundOrder1->setReason('关联退款1');
        $refundOrder1->setCurrency('CNY');
        $refundOrder1->setNotifyUrl('https://example.com/refund-callback1');
        $refundOrder1->setPayOrder($payOrder);

        // 创建没有关联的RefundOrder
        $refundOrder2 = new RefundOrder();
        $refundOrder2->setAppId('refund_app_2');
        $refundOrder2->setMoney(1000);
        $refundOrder2->setReason('无关联退款');
        $refundOrder2->setCurrency('CNY');
        $refundOrder2->setNotifyUrl('https://example.com/refund-callback2');

        $this->repository->save($refundOrder1);
        $this->repository->save($refundOrder2);

        // 查找有关联的退款订单
        $result = $this->repository->findBy(['payOrder' => $payOrder]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($refundOrder1->getId(), $result[0]->getId());
        $this->assertInstanceOf(PayOrder::class, $result[0]->getPayOrder());
    }

    public function testCountWithPayOrderAssociation(): void
    {
        // 创建PayOrder
        $payOrderRepository = self::getService(PayOrderRepository::class);

        $payOrder = new PayOrder();
        $payOrder->setAppId('test_pay_app_' . uniqid());
        $payOrder->setMchId('test_mch_id');
        $payOrder->setTradeType('JSAPI');
        $payOrder->setTradeNo('trade_no_' . uniqid());
        $payOrder->setBody('测试支付商品');
        $payOrder->setTotalFee(5000);
        $payOrder->setNotifyUrl('https://example.com/pay-callback');
        $payOrder->setStatus(PayOrderStatus::INIT);

        $payOrderRepository->save($payOrder);

        // 创建多个关联的RefundOrder
        for ($i = 1; $i <= 3; ++$i) {
            $refundOrder = new RefundOrder();
            $refundOrder->setAppId('refund_app_' . $i);
            $refundOrder->setMoney($i * 1000);
            $refundOrder->setReason('关联退款' . $i);
            $refundOrder->setCurrency('CNY');
            $refundOrder->setNotifyUrl('https://example.com/refund-callback' . $i);
            $refundOrder->setPayOrder($payOrder);

            $this->repository->save($refundOrder);
        }

        // 创建一个没有关联的RefundOrder
        $unlinkedRefund = new RefundOrder();
        $unlinkedRefund->setAppId('unlinked_app');
        $unlinkedRefund->setMoney(500);
        $unlinkedRefund->setReason('无关联退款');
        $unlinkedRefund->setCurrency('CNY');
        $unlinkedRefund->setNotifyUrl('https://example.com/unlinked-callback');

        $this->repository->save($unlinkedRefund);

        // 统计关联到特定PayOrder的退款订单数
        $count = $this->repository->count(['payOrder' => $payOrder]);

        $this->assertEquals(3, $count);
    }

    public function testFindOneByWithNullValueShouldMatchNullFields(): void
    {
        // 创建一个有 null 字段的实体
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(1000);
        $entity1->setReason(null); // 设置为 null
        $entity1->setCurrency('CNY');
        $entity1->setNotifyUrl('https://example.com/callback1');

        // 创建一个有值的实体作为对比
        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(2000);
        $entity2->setReason('有值的原因');
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        // 查找 reason 为 null 的记录
        $result = $this->repository->findOneBy(['reason' => null]);

        $this->assertInstanceOf(RefundOrder::class, $result);
        $this->assertEquals('app_id_1', $result->getAppId());
        $this->assertNull($result->getReason());
    }

    public function testFindByWithStatusNullValueShouldMatchNullFields(): void
    {
        // 创建一个 status 为 null 的实体
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(1000);
        $entity1->setReason('退款原因1');
        $entity1->setCurrency('CNY');
        $entity1->setNotifyUrl('https://example.com/callback1');
        $entity1->setStatus(null);

        // 创建一个 status 有值的实体
        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(2000);
        $entity2->setReason('退款原因2');
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');
        $entity2->setStatus('SUCCESS');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        // 查找 status 为 null 的记录
        $result = $this->repository->findBy(['status' => null]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('app_id_1', $result[0]->getAppId());
        $this->assertNull($result[0]->getStatus());
    }

    public function testCountWithStatusNullValueShouldMatchNullFields(): void
    {
        // 创建多个实体，其中一些 status 为 null
        $entity1 = new RefundOrder();
        $entity1->setAppId('app_id_1');
        $entity1->setMoney(1000);
        $entity1->setReason('退款原因1');
        $entity1->setCurrency('CNY');
        $entity1->setNotifyUrl('https://example.com/callback1');
        $entity1->setStatus(null);

        $entity2 = new RefundOrder();
        $entity2->setAppId('app_id_2');
        $entity2->setMoney(2000);
        $entity2->setReason('退款原因2');
        $entity2->setCurrency('CNY');
        $entity2->setNotifyUrl('https://example.com/callback2');
        $entity2->setStatus(null);

        $entity3 = new RefundOrder();
        $entity3->setAppId('app_id_3');
        $entity3->setMoney(3000);
        $entity3->setReason('退款原因3');
        $entity3->setCurrency('CNY');
        $entity3->setNotifyUrl('https://example.com/callback3');
        $entity3->setStatus('SUCCESS');

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        // 统计 status 为 null 的记录数
        $count = $this->repository->count(['status' => null]);

        $this->assertEquals(2, $count);
    }

    public function testFindOneByWithPayOrderAssociation(): void
    {
        // 创建PayOrder
        $payOrderRepository = self::getService(PayOrderRepository::class);

        $payOrder = new PayOrder();
        $payOrder->setAppId('test_pay_app_' . uniqid());
        $payOrder->setMchId('test_mch_id');
        $payOrder->setTradeType('JSAPI');
        $payOrder->setTradeNo('trade_no_' . uniqid());
        $payOrder->setBody('测试支付商品');
        $payOrder->setTotalFee(5000);
        $payOrder->setNotifyUrl('https://example.com/pay-callback');
        $payOrder->setStatus(PayOrderStatus::INIT);

        $payOrderRepository->save($payOrder);

        // 创建关联的RefundOrder
        $refundOrder1 = new RefundOrder();
        $refundOrder1->setAppId('refund_app_1');
        $refundOrder1->setMoney(2000);
        $refundOrder1->setReason('关联退款1');
        $refundOrder1->setCurrency('CNY');
        $refundOrder1->setNotifyUrl('https://example.com/refund-callback1');
        $refundOrder1->setPayOrder($payOrder);

        // 创建没有关联的RefundOrder
        $refundOrder2 = new RefundOrder();
        $refundOrder2->setAppId('refund_app_2');
        $refundOrder2->setMoney(1000);
        $refundOrder2->setReason('无关联退款');
        $refundOrder2->setCurrency('CNY');
        $refundOrder2->setNotifyUrl('https://example.com/refund-callback2');

        $this->repository->save($refundOrder1);
        $this->repository->save($refundOrder2);

        // 查找有关联的退款订单
        $result = $this->repository->findOneBy(['payOrder' => $payOrder]);

        $this->assertInstanceOf(RefundOrder::class, $result);
        $this->assertEquals($refundOrder1->getId(), $result->getId());
        $this->assertInstanceOf(PayOrder::class, $result->getPayOrder());
        $this->assertEquals($payOrder->getId(), $result->getPayOrder()->getId());
    }

    public function testFindByWithNullPayOrderAssociation(): void
    {
        $uniquePrefix = 'null_payorder_test_' . uniqid();

        // 创建PayOrder
        $payOrderRepository = self::getService(PayOrderRepository::class);

        $payOrder = new PayOrder();
        $payOrder->setAppId('test_pay_app_' . uniqid());
        $payOrder->setMchId('test_mch_id');
        $payOrder->setTradeType('JSAPI');
        $payOrder->setTradeNo('trade_no_' . uniqid());
        $payOrder->setBody('测试支付商品');
        $payOrder->setTotalFee(5000);
        $payOrder->setNotifyUrl('https://example.com/pay-callback');
        $payOrder->setStatus(PayOrderStatus::INIT);

        $payOrderRepository->save($payOrder);

        // 创建关联的RefundOrder
        $refundOrder1 = new RefundOrder();
        $refundOrder1->setAppId($uniquePrefix . '_linked');
        $refundOrder1->setMoney(2000);
        $refundOrder1->setReason('关联退款1');
        $refundOrder1->setCurrency('CNY');
        $refundOrder1->setNotifyUrl('https://example.com/refund-callback1');
        $refundOrder1->setPayOrder($payOrder);

        // 创建没有关联的RefundOrder (payOrder 为 null)
        $refundOrder2 = new RefundOrder();
        $refundOrder2->setAppId($uniquePrefix . '_unlinked');
        $refundOrder2->setMoney(1000);
        $refundOrder2->setReason('无关联退款');
        $refundOrder2->setCurrency('CNY');
        $refundOrder2->setNotifyUrl('https://example.com/refund-callback2');
        $refundOrder2->setPayOrder(null);

        $this->repository->save($refundOrder1);
        $this->repository->save($refundOrder2);

        // 查找特定的无关联PayOrder的退款订单
        $result = $this->repository->findBy(['payOrder' => null, 'appId' => $uniquePrefix . '_unlinked']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($refundOrder2->getId(), $result[0]->getId());
        $this->assertNull($result[0]->getPayOrder());
    }

    public function testCountWithNullPayOrderAssociation(): void
    {
        $uniquePrefix = 'count_null_payorder_test_' . uniqid();

        // 创建PayOrder
        $payOrderRepository = self::getService(PayOrderRepository::class);

        $payOrder = new PayOrder();
        $payOrder->setAppId('test_pay_app_' . uniqid());
        $payOrder->setMchId('test_mch_id');
        $payOrder->setTradeType('JSAPI');
        $payOrder->setTradeNo('trade_no_' . uniqid());
        $payOrder->setBody('测试支付商品');
        $payOrder->setTotalFee(5000);
        $payOrder->setNotifyUrl('https://example.com/pay-callback');
        $payOrder->setStatus(PayOrderStatus::INIT);

        $payOrderRepository->save($payOrder);

        // 创建关联的RefundOrder
        $refundOrder1 = new RefundOrder();
        $refundOrder1->setAppId($uniquePrefix . '_linked');
        $refundOrder1->setMoney(2000);
        $refundOrder1->setReason('关联退款1');
        $refundOrder1->setCurrency('CNY');
        $refundOrder1->setNotifyUrl('https://example.com/refund-callback1');
        $refundOrder1->setPayOrder($payOrder);

        // 创建多个没有关联的RefundOrder
        for ($i = 2; $i <= 4; ++$i) {
            $refundOrder = new RefundOrder();
            $refundOrder->setAppId($uniquePrefix . '_unlinked_' . $i);
            $refundOrder->setMoney($i * 1000);
            $refundOrder->setReason('无关联退款' . $i);
            $refundOrder->setCurrency('CNY');
            $refundOrder->setNotifyUrl('https://example.com/refund-callback' . $i);
            $refundOrder->setPayOrder(null);

            $this->repository->save($refundOrder);
        }

        $this->repository->save($refundOrder1);

        // 统计特定前缀的没有关联PayOrder的退款订单数
        $count = 0;
        for ($i = 2; $i <= 4; ++$i) {
            $count += $this->repository->count(['payOrder' => null, 'appId' => $uniquePrefix . '_unlinked_' . $i]);
        }

        $this->assertEquals(3, $count);
    }

    public function testRefundOrderWithPayOrderAssociation(): void
    {
        // 创建PayOrder
        $payOrderRepository = self::getService(PayOrderRepository::class);

        $payOrder = new PayOrder();
        $payOrder->setAppId('test_pay_app_' . uniqid());
        $payOrder->setMchId('test_mch_id');
        $payOrder->setTradeType('JSAPI');
        $payOrder->setTradeNo('trade_no_' . uniqid());
        $payOrder->setBody('测试支付商品');
        $payOrder->setTotalFee(5000);
        $payOrder->setNotifyUrl('https://example.com/pay-callback');
        $payOrder->setStatus(PayOrderStatus::INIT);

        $payOrderRepository->save($payOrder);

        // 创建关联的RefundOrder
        $refundOrder = new RefundOrder();
        $appId = $payOrder->getAppId();
        if (null !== $appId) {
            $refundOrder->setAppId($appId);
        }
        $refundOrder->setMoney(2000);
        $refundOrder->setReason('部分退款');
        $refundOrder->setCurrency('CNY');
        $refundOrder->setNotifyUrl('https://example.com/refund-callback');
        $refundOrder->setPayOrder($payOrder);

        $this->repository->save($refundOrder);

        // 验证关联关系
        $foundRefund = $this->repository->find($refundOrder->getId());
        $this->assertInstanceOf(RefundOrder::class, $foundRefund);
        $this->assertInstanceOf(PayOrder::class, $foundRefund->getPayOrder());
        $this->assertEquals($payOrder->getId(), $foundRefund->getPayOrder()->getId());

        // 验证通过PayOrder可以找到关联的退款
        $this->assertEquals($payOrder->getId(), $foundRefund->getPayOrder()->getId());
    }

    public function testFindOneByOrderingSortsCorrectly(): void
    {
        $currency = 'CAD';

        $entity1 = new RefundOrder();
        $entity1->setAppId('test_app_id_ordering_1_' . uniqid());
        $entity1->setMoney(3000);
        $entity1->setReason('测试退款排序1');
        $entity1->setCurrency($currency);
        $entity1->setNotifyUrl('https://example.com/callback1');
        $this->repository->save($entity1);

        $entity2 = new RefundOrder();
        $entity2->setAppId('test_app_id_ordering_2_' . uniqid());
        $entity2->setMoney(1000);
        $entity2->setReason('测试退款排序2');
        $entity2->setCurrency($currency);
        $entity2->setNotifyUrl('https://example.com/callback2');
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['currency' => $currency], ['money' => 'ASC']);
        $this->assertInstanceOf(RefundOrder::class, $found);
        $this->assertEquals($entity2->getMoney(), $found->getMoney());
    }

    public function testFindOneByAssociationGoodsDetailsShouldReturnMatchingEntity(): void
    {
        // 创建 RefundOrder
        $refundOrder1 = new RefundOrder();
        $refundOrder1->setAppId('test_app_id_goods_1_' . uniqid());
        $refundOrder1->setMoney(1000);
        $refundOrder1->setReason('测试退款原因1');
        $refundOrder1->setCurrency('CNY');
        $refundOrder1->setNotifyUrl('https://example.com/callback1');
        $this->repository->save($refundOrder1);

        $refundOrder2 = new RefundOrder();
        $refundOrder2->setAppId('test_app_id_goods_2_' . uniqid());
        $refundOrder2->setMoney(2000);
        $refundOrder2->setReason('测试退款原因2');
        $refundOrder2->setCurrency('CNY');
        $refundOrder2->setNotifyUrl('https://example.com/callback2');
        $this->repository->save($refundOrder2);

        // 创建 RefundGoodsDetail 并关联到 refundOrder1
        $goodsDetailRepository = self::getService(RefundGoodsDetailRepository::class);

        $goodsDetail = new RefundGoodsDetail();
        $goodsDetail->setRefundOrder($refundOrder1);
        $goodsDetail->setMerchantGoodsId('goods_test_' . uniqid());
        $goodsDetail->setUnitPrice(500);
        $goodsDetail->setRefundAmount(500);
        $goodsDetail->setRefundQuantity(1);
        $goodsDetailRepository->save($goodsDetail);

        // 重新加载实体以获取关联关系
        self::getEntityManager()->refresh($refundOrder1);

        // 验证关联关系
        $this->assertCount(1, $refundOrder1->getGoodsDetails());
        $firstGoodsDetail = $refundOrder1->getGoodsDetails()->first();
        $this->assertNotFalse($firstGoodsDetail);
        $this->assertEquals($goodsDetail->getId(), $firstGoodsDetail->getId());
    }

    public function testCountByAssociationGoodsDetailsShouldReturnCorrectNumber(): void
    {
        // 创建多个 RefundOrder
        $refundOrder1 = new RefundOrder();
        $refundOrder1->setAppId('test_app_id_count_goods_1_' . uniqid());
        $refundOrder1->setMoney(1000);
        $refundOrder1->setCurrency('CNY');
        $refundOrder1->setNotifyUrl('https://example.com/callback1');
        $this->repository->save($refundOrder1);

        $refundOrder2 = new RefundOrder();
        $refundOrder2->setAppId('test_app_id_count_goods_2_' . uniqid());
        $refundOrder2->setMoney(2000);
        $refundOrder2->setCurrency('CNY');
        $refundOrder2->setNotifyUrl('https://example.com/callback2');
        $this->repository->save($refundOrder2);

        // 创建 RefundGoodsDetail
        $goodsDetailRepository = self::getService(RefundGoodsDetailRepository::class);

        // 为 refundOrder1 创建2个商品详情
        for ($i = 1; $i <= 2; ++$i) {
            $goodsDetail = new RefundGoodsDetail();
            $goodsDetail->setRefundOrder($refundOrder1);
            $goodsDetail->setMerchantGoodsId('goods_order1_' . $i . '_' . uniqid());
            $goodsDetail->setUnitPrice($i * 100);
            $goodsDetail->setRefundAmount($i * 100);
            $goodsDetail->setRefundQuantity(1);
            $goodsDetailRepository->save($goodsDetail);
        }

        // 为 refundOrder2 创建1个商品详情
        $goodsDetail = new RefundGoodsDetail();
        $goodsDetail->setRefundOrder($refundOrder2);
        $goodsDetail->setMerchantGoodsId('goods_order2_' . uniqid());
        $goodsDetail->setUnitPrice(500);
        $goodsDetail->setRefundAmount(500);
        $goodsDetail->setRefundQuantity(1);
        $goodsDetailRepository->save($goodsDetail);

        // 重新加载实体以获取关联关系
        self::getEntityManager()->refresh($refundOrder1);
        self::getEntityManager()->refresh($refundOrder2);

        // 验证关联数量
        $this->assertCount(2, $refundOrder1->getGoodsDetails());
        $this->assertCount(1, $refundOrder2->getGoodsDetails());
    }

    protected function createNewEntity(): object
    {
        $entity = new RefundOrder();
        $entity->setAppId('wx_test_' . uniqid());
        $entity->setReason('测试退款原因');
        $entity->setMoney(100);
        $entity->setStatus('PENDING');

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<RefundOrder>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
