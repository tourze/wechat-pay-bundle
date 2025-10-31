<?php

namespace WechatPayBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use WechatPayBundle\Entity\RefundGoodsDetail;

/**
 * @extends ServiceEntityRepository<RefundGoodsDetail>
 */
#[AsRepository(entityClass: RefundGoodsDetail::class)]
class RefundGoodsDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundGoodsDetail::class);
    }

    public function save(RefundGoodsDetail $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RefundGoodsDetail $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
