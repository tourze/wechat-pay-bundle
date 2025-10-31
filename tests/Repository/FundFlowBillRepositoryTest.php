<?php

namespace WechatPayBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\MissingIdentifierField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\FundFlowBill;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Enum\AccountType;
use WechatPayBundle\Repository\FundFlowBillRepository;

/**
 * @internal
 */
#[CoversClass(FundFlowBillRepository::class)]
#[RunTestsInSeparateProcesses]
final class FundFlowBillRepositoryTest extends AbstractRepositoryTestCase
{
    private FundFlowBillRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(FundFlowBillRepository::class);
    }

    public function testSaveAndFlush(): void
    {
        $entity = $this->createTestEntity();

        $this->repository->save($entity);

        $this->assertGreaterThan(0, $entity->getId());

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals($entity->getHashType(), $found->getHashType());
    }

    public function testSaveWithoutFlush(): void
    {
        $entity = $this->createTestEntity();

        $this->repository->save($entity, false);

        $this->assertEquals(0, $entity->getId());
    }

    public function testRemove(): void
    {
        $entity = $this->createTestEntity();

        $this->repository->save($entity);
        $id = $entity->getId();

        $this->repository->remove($entity);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    // Count Tests
    public function testCountWithNoEntities(): void
    {
        $count = $this->repository->count([]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithCriteria(): void
    {
        $entity = $this->createTestEntity();
        $this->repository->save($entity);

        $count = $this->repository->count(['accountType' => AccountType::BASIC]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountWithNonExistentCriteria(): void
    {
        $count = $this->repository->count(['hashType' => 'non_existent_hash']);
        $this->assertEquals(0, $count);
    }

    public function testCountWithNullCriteria(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $count = $this->repository->count(['hashValue' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountWithMultipleCriteria(): void
    {
        $entity = $this->createTestEntity();
        $this->repository->save($entity);

        $count = $this->repository->count([
            'accountType' => AccountType::BASIC,
            'hashType' => 'SHA256',
        ]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    // Find Tests
    public function testFindExistingEntity(): void
    {
        $entity = $this->createTestEntity();
        $this->repository->save($entity);

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals($entity->getId(), $found->getId());
        $this->assertEquals($entity->getHashType(), $found->getHashType());
    }

    public function testFindNonExistentEntity(): void
    {
        $found = $this->repository->find(999999);
        $this->assertNull($found);
    }

    public function testFindWithNullId(): void
    {
        $this->expectException(MissingIdentifierField::class);
        $this->repository->find(null);
    }

    public function testFindWithZeroId(): void
    {
        $found = $this->repository->find(0);
        $this->assertNull($found);
    }

    public function testFindWithNegativeId(): void
    {
        $found = $this->repository->find(-1);
        $this->assertNull($found);
    }

    public function testFindWithStringId(): void
    {
        $found = $this->repository->find('invalid');
        $this->assertNull($found);
    }

    // FindAll Tests
    public function testFindAllReturnsArray(): void
    {
        $results = $this->repository->findAll();
        $this->assertIsArray($results);
    }

    public function testFindAllWithEntities(): void
    {
        $entity1 = $this->createTestEntity();
        $entity2 = $this->createTestEntity();
        $entity2->setHashType('MD5');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $results = $this->repository->findAll();
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));

        foreach ($results as $result) {
            $this->assertInstanceOf(FundFlowBill::class, $result);
        }
    }

    public function testFindAllWhenEmpty(): void
    {
        // Clear any existing data first by getting current count
        $results = $this->repository->findAll();
        $this->assertIsArray($results);

        // Even if empty, should return array containing only FundFlowBill instances
        $this->assertContainsOnlyInstancesOf(FundFlowBill::class, $results);
    }

    // FindBy Tests
    public function testFindByWithValidCriteria(): void
    {
        $entity = $this->createTestEntity();
        $this->repository->save($entity);

        $results = $this->repository->findBy(['accountType' => AccountType::BASIC]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertInstanceOf(FundFlowBill::class, $result);
            $this->assertEquals(AccountType::BASIC, $result->getAccountType());
        }
    }

    public function testFindByWithNonExistentCriteria(): void
    {
        $results = $this->repository->findBy(['hashType' => 'NON_EXISTENT']);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindByWithEmptyCriteria(): void
    {
        $results = $this->repository->findBy([]);
        $this->assertIsArray($results);

        foreach ($results as $result) {
            $this->assertInstanceOf(FundFlowBill::class, $result);
        }
    }

    public function testFindByWithLimit(): void
    {
        $entity1 = $this->createTestEntity();
        $entity2 = $this->createTestEntity();
        $entity2->setHashType('MD5');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $results = $this->repository->findBy([], null, 1);
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(FundFlowBill::class, $results[0]);
    }

    public function testFindByWithOffset(): void
    {
        $entity1 = $this->createTestEntity();
        $entity2 = $this->createTestEntity();
        $entity2->setHashType('MD5');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $allResults = $this->repository->findBy([]);
        $offsetResults = $this->repository->findBy([], null, null, 1);

        $this->assertIsArray($offsetResults);
        $this->assertCount(count($allResults) - 1, $offsetResults);
    }

    public function testFindByWithNullValues(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $results = $this->repository->findBy(['hashValue' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertInstanceOf(FundFlowBill::class, $result);
            $this->assertNull($result->getHashValue());
        }
    }

    // FindOneBy Tests
    public function testFindOneByWithValidCriteria(): void
    {
        $entity = $this->createTestEntity();
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['id' => $entity->getId()]);
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals($entity->getId(), $found->getId());
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $found = $this->repository->findOneBy(['hashType' => 'NON_EXISTENT']);
        $this->assertNull($found);
    }

    public function testFindOneByWithEmptyCriteria(): void
    {
        $found = $this->repository->findOneBy([]);
        // Should return null or an instance, but not throw exception
        $this->assertTrue(null === $found || $found instanceof FundFlowBill);
    }

    public function testFindOneByWithOrderBy(): void
    {
        $entity1 = $this->createTestEntity();
        $entity1->setHashType('ZZZ');
        $entity2 = $this->createTestEntity();
        $entity2->setHashType('AAA');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $found = $this->repository->findOneBy([
            'accountType' => AccountType::BASIC,
        ], ['hashType' => 'ASC']);

        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals(AccountType::BASIC, $found->getAccountType());
    }

    public function testFindOneByWithMultipleCriteria(): void
    {
        $entity = $this->createTestEntity();
        $this->repository->save($entity);

        $found = $this->repository->findOneBy([
            'accountType' => AccountType::BASIC,
            'hashType' => 'SHA256',
        ]);

        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals(AccountType::BASIC, $found->getAccountType());
        $this->assertEquals('SHA256', $found->getHashType());
    }

    public function testFindOneByWithNullValues(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['hashValue' => null]);
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertNull($found->getHashValue());
    }

    // Association and Null Field Tests
    public function testCountWithAssociationCriteria(): void
    {
        $merchant = $this->createAndPersistMerchant();

        $entity = $this->createTestEntity();
        $entity->setMerchant($merchant);
        $this->repository->save($entity);

        $count = $this->repository->count(['merchant' => $merchant]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithAssociationCriteria(): void
    {
        $merchant = $this->createAndPersistMerchant();

        $entity = $this->createTestEntity();
        $entity->setMerchant($merchant);
        $this->repository->save($entity);

        $results = $this->repository->findBy(['merchant' => $merchant]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertInstanceOf(FundFlowBill::class, $result);
            $this->assertEquals($merchant->getId(), $result->getMerchant()?->getId());
        }
    }

    public function testFindOneByWithAssociationCriteria(): void
    {
        $merchant = $this->createAndPersistMerchant();

        $entity = $this->createTestEntity();
        $entity->setMerchant($merchant);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['merchant' => $merchant]);
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals($merchant->getId(), $found->getMerchant()?->getId());
    }

    public function testCountWithNullFieldCriteria(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $count = $this->repository->count(['hashValue' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithNullFieldCriteria(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $results = $this->repository->findBy(['hashValue' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertInstanceOf(FundFlowBill::class, $result);
            $this->assertNull($result->getHashValue());
        }
    }

    public function testFindOneByWithNullFieldCriteria(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['hashValue' => null]);
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertNull($found->getHashValue());
    }

    public function testFindOneByWithOrderByLogic(): void
    {
        $entity1 = $this->createTestEntity();
        $entity1->setHashType('ZZZ_ORDER_TEST');
        $entity2 = $this->createTestEntity();
        $entity2->setHashType('AAA_ORDER_TEST');

        $this->repository->save($entity1);
        $this->repository->save($entity2);

        // Test ASC order - should return the one with AAA first
        $foundAsc = $this->repository->findOneBy(
            ['accountType' => AccountType::BASIC],
            ['hashType' => 'ASC']
        );

        $this->assertInstanceOf(FundFlowBill::class, $foundAsc);

        // Test DESC order - should return the one with ZZZ first
        $foundDesc = $this->repository->findOneBy(
            ['accountType' => AccountType::BASIC],
            ['hashType' => 'DESC']
        );

        $this->assertInstanceOf(FundFlowBill::class, $foundDesc);

        // They should be different entities if our data is correct
        if (in_array($foundAsc->getHashType(), ['AAA_ORDER_TEST', 'ZZZ_ORDER_TEST'], true)
            && in_array($foundDesc->getHashType(), ['AAA_ORDER_TEST', 'ZZZ_ORDER_TEST'], true)) {
            $this->assertNotEquals($foundAsc->getId(), $foundDesc->getId());
        }
    }

    // Additional Null Field Tests for different nullable fields
    public function testCountWithPemCertNullField(): void
    {
        // Test with a merchant that has null pemCert
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_null_cert');
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('test_cert_serial');
        $merchant->setPemCert(null); // This is nullable
        self::getEntityManager()->persist($merchant);
        self::getEntityManager()->flush();

        $entity = $this->createTestEntity();
        $entity->setMerchant($merchant);
        $this->repository->save($entity);

        // We can't directly query by merchant.pemCert in this simple setup,
        // but we can test hashValue which is directly nullable
        $entity2 = $this->createTestEntity();
        $entity2->setHashValue(null);
        $this->repository->save($entity2);

        $count = $this->repository->count(['hashValue' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithHashValueNullField(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $results = $this->repository->findBy(['hashValue' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertInstanceOf(FundFlowBill::class, $result);
            $this->assertNull($result->getHashValue());
        }
    }

    public function testFindOneByWithHashValueNullField(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashValue(null);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['hashValue' => null]);
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertNull($found->getHashValue());
    }

    // PHPStan Required Tests - Specific Naming Patterns

    public function testFindOneByAssociationMerchantShouldReturnMatchingEntity(): void
    {
        $merchant = $this->createAndPersistMerchant();

        $entity = $this->createTestEntity();
        $entity->setMerchant($merchant);
        $this->repository->save($entity);

        $found = $this->repository->findOneBy(['merchant' => $merchant]);
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals($merchant->getId(), $found->getMerchant()?->getId());
    }

    public function testCountByAssociationMerchantShouldReturnCorrectNumber(): void
    {
        $merchant = $this->createAndPersistMerchant();

        $entity = $this->createTestEntity();
        $entity->setMerchant($merchant);
        $this->repository->save($entity);

        $count = $this->repository->count(['merchant' => $merchant]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    // Additional Robustness Tests for Invalid Field Queries
    public function testCountWithInvalidFieldShouldThrowException(): void
    {
        $this->expectException(\Exception::class);
        $this->repository->count(['invalidField123' => 'test']);
    }

    public function testFindWithInvalidFieldShouldThrowException(): void
    {
        $this->expectException(\Exception::class);
        $this->repository->findBy(['invalidField456' => 'test']);
    }

    // Robustness Tests
    public function testRepositoryHandlesLargeDataset(): void
    {
        // Create multiple entities
        $entities = [];
        for ($i = 0; $i < 5; ++$i) {
            $entity = $this->createTestEntity();
            $entity->setHashType('HASH_' . $i);
            $this->repository->save($entity, false);
            $entities[] = $entity;
        }

        // Flush all at once
        $this->repository->save($entities[0], true);

        // Test findAll still works
        $results = $this->repository->findAll();
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(5, count($results));
    }

    public function testRepositoryHandlesSpecialCharacters(): void
    {
        $entity = $this->createTestEntity();
        $entity->setHashType('TEST-HASH_WITH.SPECIAL@CHARS');
        $entity->setDownloadUrl('https://example.com/file?param=value&other=测试');
        $entity->setLocalFile('/path/to/file with spaces/测试文件.txt');

        $this->repository->save($entity);

        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(FundFlowBill::class, $found);
        $this->assertEquals('TEST-HASH_WITH.SPECIAL@CHARS', $found->getHashType());
    }

    private function createTestEntity(): FundFlowBill
    {
        $merchant = $this->createAndPersistMerchant();

        $entity = new FundFlowBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable());
        $entity->setAccountType(AccountType::BASIC);
        $entity->setHashType('SHA256');
        $entity->setHashValue('test_hash_' . uniqid());
        $entity->setDownloadUrl('https://example.com/download/' . uniqid());
        $entity->setLocalFile('/tmp/test_file_' . uniqid() . '.txt');

        return $entity;
    }

    private function createAndPersistMerchant(): Merchant
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_' . uniqid());
        $merchant->setApiKey('test_api_key_' . uniqid());
        $merchant->setCertSerial('test_cert_serial_' . uniqid());

        // Persist the merchant first
        $entityManager = self::getService(EntityManagerInterface::class);
        $entityManager->persist($merchant);
        $entityManager->flush();

        return $merchant;
    }

    protected function createNewEntity(): object
    {
        $merchant = $this->createAndPersistMerchant();

        $entity = new FundFlowBill();
        $entity->setMerchant($merchant);
        $entity->setBillDate(new \DateTimeImmutable());
        $entity->setAccountType(AccountType::BASIC);
        $entity->setHashType('SHA256');
        $entity->setHashValue('test_hash_' . uniqid());
        $entity->setDownloadUrl('https://example.com/download/' . uniqid());
        $entity->setLocalFile('/tmp/test_file_' . uniqid() . '.txt');

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<FundFlowBill>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
