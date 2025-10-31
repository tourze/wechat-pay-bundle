<?php

namespace WechatPayBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\MissingIdentifierField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Repository\MerchantRepository;

/**
 * @internal
 */
#[CoversClass(MerchantRepository::class)]
#[RunTestsInSeparateProcesses]
final class MerchantRepositoryTest extends AbstractRepositoryTestCase
{
    private MerchantRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(MerchantRepository::class);
    }

    public function testSaveAndFlush(): void
    {
        $entity = new Merchant();
        $entity->setMchId('test_mch_' . uniqid());
        $entity->setApiKey('test_api_key_' . uniqid());
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);

        $this->repository->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(Merchant::class, $found);
        $this->assertEquals($entity->getMchId(), $found->getMchId());
    }

    public function testSaveWithoutFlush(): void
    {
        $entity = new Merchant();
        $entity->setMchId('test_mch_' . uniqid());
        $entity->setApiKey('test_api_key_' . uniqid());
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);

        $this->repository->save($entity, false);

        // 在没有flush的情况下，实体可能已经有ID但还未持久化到数据库
        $this->assertNotNull($entity->getId());
    }

    public function testRemove(): void
    {
        $entity = new Merchant();
        $entity->setMchId('test_mch_' . uniqid());
        $entity->setApiKey('test_api_key_' . uniqid());
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);

        $this->repository->save($entity);
        $id = $entity->getId();

        $this->repository->remove($entity);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testCount(): void
    {
        $initialCount = $this->repository->count([]);

        $entity = new Merchant();
        $entity->setMchId('test_mch_' . uniqid());
        $entity->setApiKey('test_api_key_' . uniqid());
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);

        $this->repository->save($entity);

        $newCount = $this->repository->count([]);
        $this->assertEquals($initialCount + 1, $newCount);
    }

    public function testCountWithCriteria(): void
    {
        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_' . uniqid());
        $entity1->setApiKey('test_api_key_' . uniqid());
        $entity1->setCertSerial('test_cert_serial_' . uniqid());
        $entity1->setValid(true);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_' . uniqid());
        $entity2->setApiKey('test_api_key_' . uniqid());
        $entity2->setCertSerial('test_cert_serial_' . uniqid());
        $entity2->setValid(false);
        $this->repository->save($entity2);

        $validCount = $this->repository->count(['valid' => true]);
        $invalidCount = $this->repository->count(['valid' => false]);

        $this->assertGreaterThanOrEqual(1, $validCount);
        $this->assertGreaterThanOrEqual(1, $invalidCount);
    }

    public function testCountWithEmptyCriteria(): void
    {
        $count = $this->repository->count([]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFind(): void
    {
        $entity = new Merchant();
        $entity->setMchId('test_mch_' . uniqid());
        $entity->setApiKey('test_api_key_' . uniqid());
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);

        $this->repository->save($entity);
        $id = $entity->getId();

        $found = $this->repository->find($id);
        $this->assertInstanceOf(Merchant::class, $found);
        $this->assertEquals($id, $found->getId());
        $this->assertEquals($entity->getMchId(), $found->getMchId());
    }

    public function testFindWithInvalidId(): void
    {
        $found = $this->repository->find(999999);
        $this->assertNull($found);
    }

    public function testFindWithNullId(): void
    {
        // 对于Merchant，传入null可能会抛出异常而不是返回null
        $this->expectException(MissingIdentifierField::class);
        $this->repository->find(null);
    }

    public function testFindAll(): void
    {
        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_1_' . uniqid());
        $entity1->setApiKey('test_api_key_1_' . uniqid());
        $entity1->setCertSerial('test_cert_serial_1_' . uniqid());
        $entity1->setValid(true);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_2_' . uniqid());
        $entity2->setApiKey('test_api_key_2_' . uniqid());
        $entity2->setCertSerial('test_cert_serial_2_' . uniqid());
        $entity2->setValid(false);
        $this->repository->save($entity2);

        $all = $this->repository->findAll();
        $this->assertIsArray($all);
        $this->assertGreaterThanOrEqual(2, count($all));

        foreach ($all as $merchant) {
            $this->assertInstanceOf(Merchant::class, $merchant);
        }
    }

    public function testFindBy(): void
    {
        $mchId = 'test_mch_findby_' . uniqid();

        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_1_' . uniqid());
        $entity1->setApiKey('test_api_key_1_' . uniqid());
        $entity1->setCertSerial('test_cert_serial_1_' . uniqid());
        $entity1->setValid(true);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_2_' . uniqid());
        $entity2->setApiKey('test_api_key_2_' . uniqid());
        $entity2->setCertSerial('test_cert_serial_2_' . uniqid());
        $entity2->setValid(false);
        $this->repository->save($entity2);

        $found = $this->repository->findBy(['valid' => true]);
        $this->assertIsArray($found);
        $this->assertGreaterThanOrEqual(1, count($found));

        foreach ($found as $merchant) {
            $this->assertInstanceOf(Merchant::class, $merchant);
            $this->assertTrue($merchant->isValid());
        }
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $entities = [];
        for ($i = 0; $i < 5; ++$i) {
            $entity = new Merchant();
            $entity->setMchId('test_mch_limit_' . $i . '_' . uniqid());
            $entity->setApiKey('test_api_key_' . $i . '_' . uniqid());
            $entity->setCertSerial('test_cert_serial_' . $i . '_' . uniqid());
            $entity->setValid(true);
            $this->repository->save($entity);
            $entities[] = $entity;
        }

        $found = $this->repository->findBy(['valid' => true], null, 2, 1);
        $this->assertIsArray($found);
        $foundCount = count($found);
        $this->assertLessThanOrEqual(2, $foundCount);
    }

    public function testFindByEmpty(): void
    {
        $found = $this->repository->findBy(['mchId' => 'non_existent_' . uniqid()]);
        $this->assertIsArray($found);
        $this->assertEmpty($found);
    }

    public function testFindOneBy(): void
    {
        $mchId = 'test_mch_findone_' . uniqid();

        $entity = new Merchant();
        $entity->setMchId($mchId);
        $entity->setApiKey('test_api_key_' . uniqid());
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['mchId' => $mchId]);
        $this->assertInstanceOf(Merchant::class, $found);
        $this->assertEquals($mchId, $found->getMchId());
        $this->assertEquals($entity->getApiKey(), $found->getApiKey());
    }

    public function testFindOneByWithOrderBy(): void
    {
        $apiKey = 'test_api_key_findone_' . uniqid();

        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_z_' . uniqid());
        $entity1->setApiKey($apiKey);
        $entity1->setCertSerial('test_cert_serial_1_' . uniqid());
        $entity1->setValid(true);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_a_' . uniqid());
        $entity2->setApiKey($apiKey);
        $entity2->setCertSerial('test_cert_serial_2_' . uniqid());
        $entity2->setValid(true);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['apiKey' => $apiKey], ['mchId' => 'ASC']);
        $this->assertInstanceOf(Merchant::class, $found);
        $this->assertEquals($entity2->getMchId(), $found->getMchId());
    }

    public function testFindOneByNotFound(): void
    {
        $found = $this->repository->findOneBy(['mchId' => 'non_existent_' . uniqid()]);
        $this->assertNull($found);
    }

    public function testFindOneByMultipleCriteria(): void
    {
        $mchId = 'test_mch_multi_' . uniqid();
        $apiKey = 'test_api_key_multi_' . uniqid();

        $entity = new Merchant();
        $entity->setMchId($mchId);
        $entity->setApiKey($apiKey);
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy([
            'mchId' => $mchId,
            'apiKey' => $apiKey,
            'valid' => true,
        ]);

        $this->assertInstanceOf(Merchant::class, $found);
        $this->assertEquals($mchId, $found->getMchId());
        $this->assertEquals($apiKey, $found->getApiKey());
        $this->assertTrue($found->isValid());
    }

    public function testFindByWithNullFieldQuery(): void
    {
        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_null_1_' . uniqid());
        $entity1->setApiKey('test_api_key_1_' . uniqid());
        $entity1->setCertSerial('test_cert_serial_1_' . uniqid());
        $entity1->setValid(true);
        $entity1->setPemCert(null);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_null_2_' . uniqid());
        $entity2->setApiKey('test_api_key_2_' . uniqid());
        $entity2->setCertSerial('test_cert_serial_2_' . uniqid());
        $entity2->setValid(true);
        $entity2->setPemCert('some_cert_content');
        $this->repository->save($entity2);

        $results = $this->repository->findBy(['pemCert' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertNull($result->getPemCert());
        }
    }

    public function testCountWithNullFieldQuery(): void
    {
        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_count_null_1_' . uniqid());
        $entity1->setApiKey('test_api_key_1_' . uniqid());
        $entity1->setCertSerial('test_cert_serial_1_' . uniqid());
        $entity1->setValid(true);
        $entity1->setPemCert(null);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_count_null_2_' . uniqid());
        $entity2->setApiKey('test_api_key_2_' . uniqid());
        $entity2->setCertSerial('test_cert_serial_2_' . uniqid());
        $entity2->setValid(true);
        $entity2->setPemCert('some_cert_content');
        $this->repository->save($entity2);

        $count = $this->repository->count(['pemCert' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindOneByWithNullFieldQuery(): void
    {
        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_findoneby_null_1_' . uniqid());
        $entity1->setApiKey('test_api_key_1_' . uniqid());
        $entity1->setCertSerial('test_cert_serial_1_' . uniqid());
        $entity1->setValid(true);
        $entity1->setPemCert(null);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_findoneby_null_2_' . uniqid());
        $entity2->setApiKey('test_api_key_2_' . uniqid());
        $entity2->setCertSerial('test_cert_serial_2_' . uniqid());
        $entity2->setValid(true);
        $entity2->setPemCert('some_cert_content');
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['pemCert' => null]);
        $this->assertInstanceOf(Merchant::class, $found);
        $this->assertNull($found->getPemCert());
    }

    public function testFindOneByOrderingSortsCorrectly(): void
    {
        $apiKey = 'test_api_key_ordering_' . uniqid();

        $entity1 = new Merchant();
        $entity1->setMchId('test_mch_z_ordering_' . uniqid());
        $entity1->setApiKey($apiKey);
        $entity1->setCertSerial('test_cert_serial_1_' . uniqid());
        $entity1->setValid(true);
        $this->repository->save($entity1);

        $entity2 = new Merchant();
        $entity2->setMchId('test_mch_a_ordering_' . uniqid());
        $entity2->setApiKey($apiKey);
        $entity2->setCertSerial('test_cert_serial_2_' . uniqid());
        $entity2->setValid(true);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['apiKey' => $apiKey], ['mchId' => 'ASC']);
        $this->assertInstanceOf(Merchant::class, $found);
        $this->assertEquals($entity2->getMchId(), $found->getMchId());
    }

    // PHPStan Required Tests - Specific Naming Patterns

    protected function createNewEntity(): object
    {
        $entity = new Merchant();
        $entity->setMchId('test_mch_' . uniqid());
        $entity->setApiKey('test_api_key_' . uniqid());
        $entity->setCertSerial('test_cert_serial_' . uniqid());
        $entity->setValid(true);

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<Merchant>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
