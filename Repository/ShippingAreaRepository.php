<?php

namespace Plugin\FlexibleShippingFee\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\FlexibleShippingFee\Entity\ShippingArea;

class ShippingAreaRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingArea::class);
    }

    public function findAllOrderBySortNo()
    {
        return $this->createQueryBuilder('sa')
            ->orderBy('sa.sort_no', 'ASC')
            ->addOrderBy('sa.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxSortNo()
    {
        $result = $this->createQueryBuilder('sa')
            ->select('MAX(sa.sort_no)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int)$result : 0;
    }

    public function findByPrefId(int $prefId): ?ShippingArea
    {
        return $this->createQueryBuilder('sa')
            ->innerJoin('sa.ShippingAreaPrefs', 'sap')
            ->where('sap.pref_id = :prefId')
            ->setParameter('prefId', $prefId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
