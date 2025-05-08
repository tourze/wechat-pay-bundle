<?php

namespace WechatPayBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WechatPayBundle\Entity\PayOrder;

/**
 * @method PayOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PayOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method PayOrder[]    findAll()
 * @method PayOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PayOrderRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayOrder::class);
    }
}
