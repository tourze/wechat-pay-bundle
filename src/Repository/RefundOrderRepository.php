<?php

namespace WechatPayBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WechatPayBundle\Entity\RefundOrder;

/**
 * @method RefundOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefundOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefundOrder[]    findAll()
 * @method RefundOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefundOrderRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundOrder::class);
    }
}
