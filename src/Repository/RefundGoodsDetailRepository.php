<?php

namespace WechatPayBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;
use WechatPayBundle\Entity\RefundGoodsDetail;

/**
 * @method RefundGoodsDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method RefundGoodsDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method RefundGoodsDetail[]    findAll()
 * @method RefundGoodsDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefundGoodsDetailRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundGoodsDetail::class);
    }
}
