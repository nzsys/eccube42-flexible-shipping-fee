<?php

namespace Plugin\FlexibleShippingFee\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\FlexibleShippingFee\Entity\ShippingRate;

class ShippingRateRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingRate::class);
    }

    public function findByAreaIdAndSize(int $areaId, int $size): ?ShippingRate
    {
        return $this->createQueryBuilder('sr')
            ->where('sr.area_id = :areaId')
            ->andWhere('sr.size = :size')
            ->setParameter('areaId', $areaId)
            ->setParameter('size', $size)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByAreaId(int $areaId)
    {
        return $this->createQueryBuilder('sr')
            ->where('sr.area_id = :areaId')
            ->setParameter('areaId', $areaId)
            ->orderBy('sr.size', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
