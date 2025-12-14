<?php

namespace Plugin\FlexibleShippingFee\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\FlexibleShippingFee\Entity\ShippingAreaPref;

class ShippingAreaPrefRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingAreaPref::class);
    }

    public function findByAreaId(int $areaId)
    {
        return $this->createQueryBuilder('sap')
            ->where('sap.area_id = :areaId')
            ->setParameter('areaId', $areaId)
            ->getQuery()
            ->getResult();
    }

    public function deleteByAreaId(int $areaId)
    {
        return $this->createQueryBuilder('sap')
            ->delete()
            ->where('sap.area_id = :areaId')
            ->setParameter('areaId', $areaId)
            ->getQuery()
            ->execute();
    }
}
