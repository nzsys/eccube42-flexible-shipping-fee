<?php

namespace Plugin\FlexibleShippingFee\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\FlexibleShippingFee\Entity\SizeConfig;

class SizeConfigRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SizeConfig::class);
    }

    public function findAllOrderBySortNo()
    {
        return $this->createQueryBuilder('sc')
            ->orderBy('sc.sort_no', 'ASC')
            ->addOrderBy('sc.min_quantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxSortNo()
    {
        $result = $this->createQueryBuilder('sc')
            ->select('MAX(sc.sort_no)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int)$result : 0;
    }

    public function findByQuantity(int $quantity): ?SizeConfig
    {
        $configs = $this->findAllOrderBySortNo();

        foreach ($configs as $config) {
            if ($config->isInRange($quantity)) {
                return $config;
            }
        }

        return null;
    }
}
