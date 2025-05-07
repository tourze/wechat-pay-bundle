<?php

namespace WechatPayBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;
use WechatPayBundle\Entity\TradeBill;

/**
 * @method TradeBill|null find($id, $lockMode = null, $lockVersion = null)
 * @method TradeBill|null findOneBy(array $criteria, array $orderBy = null)
 * @method TradeBill[]    findAll()
 * @method TradeBill[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeBillRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradeBill::class);
    }
}
