<?php

namespace Plugin\FlexibleShippingFee\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\FlexibleShippingFee\Entity\ShippingBreakdown;

class ShippingBreakdownRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingBreakdown::class);
    }

    public function findByOrderId(int $orderId)
    {
        return $this->createQueryBuilder('sb')
            ->where('sb.order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('sb.shipping_id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByShippingId(int $shippingId): ?ShippingBreakdown
    {
        return $this->createQueryBuilder('sb')
            ->where('sb.shipping_id = :shippingId')
            ->setParameter('shippingId', $shippingId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteByShippingId(int $shippingId)
    {
        return $this->createQueryBuilder('sb')
            ->delete()
            ->where('sb.shipping_id = :shippingId')
            ->setParameter('shippingId', $shippingId)
            ->getQuery()
            ->execute();
    }
}
