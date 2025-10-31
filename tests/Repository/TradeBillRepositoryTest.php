<?php

namespace WechatPayBundle\Tests\Repository;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\MissingIdentifierField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\TradeBill;
use WechatPayBundle\Enum\BillType;
use WechatPayBundle\Repository\TradeBillRepository;

/**
 * @internal
 */
#[CoversClass(TradeBillRepository::class)]
#[RunTestsInSeparateProcesses]
final class TradeBillRepositoryTest extends AbstractRepositoryTestCase
{
    private TradeBillRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(TradeBillRepository::class);
    }

    public function testSaveAndFlush(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile('/tmp/bill_' . uniqid() . '.csv');

        $this->repository->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($entity->getBillDate(), $found->getBillDate());
        $this->assertEquals($entity->getBillType(), $found->getBillType());
        $this->assertEquals($entity->getHashType(), $found->getHashType());
        $this->assertEquals($entity->getDownloadUrl(), $found->getDownloadUrl());
        $this->assertEquals($entity->getLocalFile(), $found->getLocalFile());
    }

    public function testSaveWithoutFlush(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile('/tmp/bill_' . uniqid() . '.csv');

        $this->repository->save($entity, false);

        $this->assertEquals(0, $entity->getId());
    }

    public function testRemove(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile('/tmp/bill_' . uniqid() . '.csv');

        $this->repository->save($entity);
        $id = $entity->getId();

        $this->repository->remove($entity);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testCount(): void
    {
        $initialCount = $this->repository->count([]);

        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile('/tmp/bill_' . uniqid() . '.csv');

        $this->repository->save($entity);

        $newCount = $this->repository->count([]);
        $this->assertEquals($initialCount + 1, $newCount);
    }

    public function testCountWithCriteria(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_' . uniqid() . '.csv');
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-02'));
        $entity2->setBillType(BillType::SUCCESS);
        $entity2->setHashType('MD5');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_' . uniqid() . '.csv');
        $this->repository->save($entity2);

        $allTypeCount = $this->repository->count(['billType' => BillType::ALL]);
        $successTypeCount = $this->repository->count(['billType' => BillType::SUCCESS]);

        $this->assertGreaterThanOrEqual(1, $allTypeCount);
        $this->assertGreaterThanOrEqual(1, $successTypeCount);
    }

    public function testCountWithEmptyCriteria(): void
    {
        $count = $this->repository->count([]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFind(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile('/tmp/bill_' . uniqid() . '.csv');

        $this->repository->save($entity);
        $id = $entity->getId();

        $found = $this->repository->find($id);
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($id, $found->getId());
        $this->assertEquals($entity->getBillDate(), $found->getBillDate());
        $this->assertEquals($entity->getBillType(), $found->getBillType());
    }

    public function testFindWithInvalidId(): void
    {
        $found = $this->repository->find(999999);
        $this->assertNull($found);
    }

    public function testFindWithNullId(): void
    {
        // 对于TradeBill，传入null可能会抛出异常而不是返回null
        $this->expectException(MissingIdentifierField::class);
        $this->repository->find(null);
    }

    public function testFindAll(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_' . uniqid() . '.csv');
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-02'));
        $entity2->setBillType(BillType::SUCCESS);
        $entity2->setHashType('MD5');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_' . uniqid() . '.csv');
        $this->repository->save($entity2);

        $all = $this->repository->findAll();
        $this->assertIsArray($all);
        $this->assertGreaterThanOrEqual(2, count($all));

        foreach ($all as $tradeBill) {
            $this->assertInstanceOf(TradeBill::class, $tradeBill);
        }
    }

    public function testFindBy(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_' . uniqid() . '.csv');
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity2->setBillType(BillType::SUCCESS);
        $entity2->setHashType('SHA1');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_' . uniqid() . '.csv');
        $this->repository->save($entity2);

        $found = $this->repository->findBy(['hashType' => 'SHA1']);
        $this->assertIsArray($found);
        $this->assertGreaterThanOrEqual(2, count($found));

        foreach ($found as $tradeBill) {
            $this->assertInstanceOf(TradeBill::class, $tradeBill);
            $this->assertEquals('SHA1', $tradeBill->getHashType());
        }
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entities = [];
        for ($i = 0; $i < 5; ++$i) {
            $entity = new TradeBill();
            $entity->setMerchant($merchant);
            $entity->setBillDate(new \DateTimeImmutable('2023-01-0' . ($i + 1)));
            $entity->setBillType(BillType::ALL);
            $entity->setHashType('SHA1');
            $entity->setDownloadUrl('https://example.com/bill' . $i . '.csv');
            $entity->setLocalFile('/tmp/bill' . $i . '_' . uniqid() . '.csv');
            $this->repository->save($entity);
            $entities[] = $entity;
        }

        $found = $this->repository->findBy(['billType' => BillType::ALL], null, 2, 1);
        $this->assertIsArray($found);
        $foundCount = count($found);
        $this->assertLessThanOrEqual(2, $foundCount);
    }

    public function testFindByEmpty(): void
    {
        $found = $this->repository->findBy(['hashType' => 'non_existent_' . uniqid()]);
        $this->assertIsArray($found);
        $this->assertEmpty($found);
    }

    public function testFindOneBy(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $localFile = '/tmp/bill_findone_' . uniqid() . '.csv';

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile($localFile);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['localFile' => $localFile]);
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($localFile, $found->getLocalFile());
        $this->assertEquals($entity->getBillDate(), $found->getBillDate());
    }

    public function testFindOneByWithOrderBy(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-02'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_' . uniqid() . '.csv');
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity2->setBillType(BillType::ALL);
        $entity2->setHashType('SHA1');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_' . uniqid() . '.csv');
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['hashType' => 'SHA1'], ['billDate' => 'ASC']);
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($entity2->getBillDate(), $found->getBillDate());
    }

    public function testFindOneByNotFound(): void
    {
        $found = $this->repository->findOneBy(['localFile' => 'non_existent_' . uniqid()]);
        $this->assertNull($found);
    }

    public function testFindOneByMultipleCriteria(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $billDate = new \DateTimeImmutable('2023-01-01');
        $hashType = 'SHA1';
        $localFile = '/tmp/bill_multi_' . uniqid() . '.csv';

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate($billDate);
        $entity->setBillType(BillType::ALL);
        $entity->setHashType($hashType);
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile($localFile);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy([
            'billDate' => $billDate,
            'hashType' => $hashType,
            'localFile' => $localFile,
            'billType' => BillType::ALL,
        ]);

        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($billDate, $found->getBillDate());
        $this->assertEquals($hashType, $found->getHashType());
        $this->assertEquals($localFile, $found->getLocalFile());
        $this->assertEquals(BillType::ALL, $found->getBillType());
    }

    public function testFindByWithNullFieldQuery(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_null_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_null_' . uniqid() . '.csv');
        $entity1->setHashValue(null);
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-02'));
        $entity2->setBillType(BillType::SUCCESS);
        $entity2->setHashType('MD5');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_null_' . uniqid() . '.csv');
        $entity2->setHashValue('some_hash_value');
        $this->repository->save($entity2);

        $results = $this->repository->findBy(['hashValue' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertNull($result->getHashValue());
        }
    }

    public function testCountWithNullFieldQuery(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_count_null_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_count_null_' . uniqid() . '.csv');
        $entity1->setHashValue(null);
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-02'));
        $entity2->setBillType(BillType::SUCCESS);
        $entity2->setHashType('MD5');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_count_null_' . uniqid() . '.csv');
        $entity2->setHashValue('some_hash_value');
        $this->repository->save($entity2);

        $count = $this->repository->count(['hashValue' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindOneByWithNullFieldQuery(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_findoneby_null_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_findoneby_null_' . uniqid() . '.csv');
        $entity1->setHashValue(null);
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-02'));
        $entity2->setBillType(BillType::SUCCESS);
        $entity2->setHashType('MD5');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_findoneby_null_' . uniqid() . '.csv');
        $entity2->setHashValue('some_hash_value');
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['hashValue' => null]);
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertNull($found->getHashValue());
    }

    public function testFindByWithMerchantAssociation(): void
    {
        $merchant1 = new Merchant();
        $merchant1->setMchId('test_mch_assoc_1_' . uniqid());
        $merchant1->setApiKey('test_api_key_1_' . uniqid());
        $merchant1->setCertSerial('test_cert_serial_1_' . uniqid());
        self::getEntityManager()->persist($merchant1);

        $merchant2 = new Merchant();
        $merchant2->setMchId('test_mch_assoc_2_' . uniqid());
        $merchant2->setApiKey('test_api_key_2_' . uniqid());
        $merchant2->setCertSerial('test_cert_serial_2_' . uniqid());
        self::getEntityManager()->persist($merchant2);

        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant1);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill1.csv');
        $entity1->setLocalFile('/tmp/bill1_assoc_' . uniqid() . '.csv');
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant1);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-02'));
        $entity2->setBillType(BillType::SUCCESS);
        $entity2->setHashType('MD5');
        $entity2->setDownloadUrl('https://example.com/bill2.csv');
        $entity2->setLocalFile('/tmp/bill2_assoc_' . uniqid() . '.csv');
        $this->repository->save($entity2);

        $entity3 = new TradeBill();
        $entity3->setMerchant($merchant2);
        $entity3->setBillDate(new \DateTimeImmutable('2023-01-03'));
        $entity3->setBillType(BillType::ALL);
        $entity3->setHashType('SHA256');
        $entity3->setDownloadUrl('https://example.com/bill3.csv');
        $entity3->setLocalFile('/tmp/bill3_assoc_' . uniqid() . '.csv');
        $this->repository->save($entity3);

        $results = $this->repository->findBy(['merchant' => $merchant1]);
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(TradeBill::class, $results);

        foreach ($results as $result) {
            $this->assertEquals($merchant1->getId(), $result->getMerchant()?->getId());
        }
    }

    public function testCountWithMerchantAssociation(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_count_assoc_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        for ($i = 1; $i <= 3; ++$i) {
            $entity = new TradeBill();
            $entity->setMerchant($merchant);
            $entity->setBillDate(new \DateTimeImmutable('2023-01-0' . $i));
            $entity->setBillType(BillType::ALL);
            $entity->setHashType('SHA1');
            $entity->setDownloadUrl('https://example.com/bill' . $i . '.csv');
            $entity->setLocalFile('/tmp/bill' . $i . '_count_assoc_' . uniqid() . '.csv');
            $this->repository->save($entity);
        }

        $count = $this->repository->count(['merchant' => $merchant]);
        $this->assertIsInt($count);
        $this->assertEquals(3, $count);
    }

    public function testFindOneByWithMerchantAssociation(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_findoneby_assoc_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill.csv');
        $entity->setLocalFile('/tmp/bill_findoneby_assoc_' . uniqid() . '.csv');
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['merchant' => $merchant]);
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($merchant->getId(), $found->getMerchant()?->getId());
    }

    public function testFindOneByOrderingSortsCorrectly(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_ordering_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity1 = new TradeBill();
        $entity1->setMerchant($merchant);
        $entity1->setBillDate(new \DateTimeImmutable('2023-01-03'));
        $entity1->setBillType(BillType::ALL);
        $entity1->setHashType('SHA1');
        $entity1->setDownloadUrl('https://example.com/bill_ordering_1.csv');
        $entity1->setLocalFile('/tmp/bill_ordering_1_' . uniqid() . '.csv');
        $this->repository->save($entity1);

        $entity2 = new TradeBill();
        $entity2->setMerchant($merchant);
        $entity2->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity2->setBillType(BillType::ALL);
        $entity2->setHashType('SHA1');
        $entity2->setDownloadUrl('https://example.com/bill_ordering_2.csv');
        $entity2->setLocalFile('/tmp/bill_ordering_2_' . uniqid() . '.csv');
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy(['hashType' => 'SHA1'], ['billDate' => 'ASC']);
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($entity2->getBillDate(), $found->getBillDate());
    }

    public function testFindOneByAssociationMerchantShouldReturnMatchingEntity(): void
    {
        $merchant1 = new Merchant();
        $merchant1->setMchId('test_mch_association_1_' . uniqid());
        $merchant1->setApiKey('test_api_key_1_' . uniqid());
        $merchant1->setCertSerial('test_cert_serial_1_' . uniqid());
        self::getEntityManager()->persist($merchant1);

        $merchant2 = new Merchant();
        $merchant2->setMchId('test_mch_association_2_' . uniqid());
        $merchant2->setApiKey('test_api_key_2_' . uniqid());
        $merchant2->setCertSerial('test_cert_serial_2_' . uniqid());
        self::getEntityManager()->persist($merchant2);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant1);
        $entity->setBillDate(new \DateTimeImmutable('2023-01-01'));
        $entity->setBillType(BillType::ALL);
        $entity->setHashType('SHA1');
        $entity->setDownloadUrl('https://example.com/bill_association.csv');
        $entity->setLocalFile('/tmp/bill_association_' . uniqid() . '.csv');
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['merchant' => $merchant1]);
        $this->assertInstanceOf(TradeBill::class, $found);
        $this->assertEquals($merchant1, $found->getMerchant());
    }

    public function testCountByAssociationMerchantShouldReturnCorrectNumber(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_count_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        for ($i = 1; $i <= 3; ++$i) {
            $entity = new TradeBill();
            $entity->setMerchant($merchant);
            $entity->setBillDate(new \DateTimeImmutable('2023-01-0' . $i));
            $entity->setBillType(BillType::ALL);
            $entity->setHashType('SHA1');
            $entity->setDownloadUrl('https://example.com/bill_count_' . $i . '.csv');
            $entity->setLocalFile('/tmp/bill_count_' . $i . '_' . uniqid() . '.csv');
            $this->repository->save($entity);
        }

        $count = $this->repository->count(['merchant' => $merchant]);
        $this->assertEquals(3, $count);
    }

    protected function createNewEntity(): object
    {
        // 创建并持久化 Merchant
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = new TradeBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(CarbonImmutable::now());
        $entity->setHashType('SHA256');
        $entity->setDownloadUrl('https://example.com/trade-bill.csv');
        $entity->setLocalFile('/tmp/trade-bill-' . uniqid() . '.csv');

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<TradeBill>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
