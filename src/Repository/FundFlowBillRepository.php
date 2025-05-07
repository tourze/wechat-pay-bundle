<?php

namespace WechatPayBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;
use WechatPayBundle\Entity\FundFlowBill;

/**
 * @method FundFlowBill|null find($id, $lockMode = null, $lockVersion = null)
 * @method FundFlowBill|null findOneBy(array $criteria, array $orderBy = null)
 * @method FundFlowBill[]    findAll()
 * @method FundFlowBill[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FundFlowBillRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FundFlowBill::class);
    }
}
