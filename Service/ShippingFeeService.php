<?php

namespace Plugin\FlexibleShippingFee\Service;

use Eccube\Entity\Shipping;
use Plugin\FlexibleShippingFee\Repository\ShippingAreaRepository;
use Plugin\FlexibleShippingFee\Repository\ShippingRateRepository;
use Plugin\FlexibleShippingFee\Repository\ShippingBreakdownRepository;
use Plugin\FlexibleShippingFee\Repository\SizeConfigRepository;
use Plugin\FlexibleShippingFee\Entity\ShippingBreakdown;

class ShippingFeeService
{
    /**
     * @var ShippingAreaRepository
     */
    private $shippingAreaRepository;

    /**
     * @var ShippingRateRepository
     */
    private $shippingRateRepository;

    /**
     * @var ShippingBreakdownRepository
     */
    private $shippingBreakdownRepository;

    /**
     * @var SizeConfigRepository
     */
    private $sizeConfigRepository;

    public function __construct(
        ShippingAreaRepository $shippingAreaRepository,
        ShippingRateRepository $shippingRateRepository,
        ShippingBreakdownRepository $shippingBreakdownRepository,
        SizeConfigRepository $sizeConfigRepository
    ) {
        $this->shippingAreaRepository = $shippingAreaRepository;
        $this->shippingRateRepository = $shippingRateRepository;
        $this->shippingBreakdownRepository = $shippingBreakdownRepository;
        $this->sizeConfigRepository = $sizeConfigRepository;
    }

    /**
     * @param Shipping $Shipping
     * @return array ['total' => int, 'breakdown' => array, 'error' => string|null]
     */
    public function calculateShippingFee(Shipping $Shipping): array
    {
        $Prefecture = $Shipping->getPref();
        if (!$Prefecture) {
            return ['total' => 0, 'breakdown' => null, 'error' => null];
        }

        $quantity = 0;
        $items = $Shipping->getProductOrderItems();
        foreach ($items as $Item) {
            $itemQty = $Item->getQuantity();
            log_info('[FlexibleShippingFee] 商品名: ' . $Item->getProductName() . ', 数量: ' . $itemQty);
            $quantity += $itemQty;
        }

        $size = $this->determineSizeByQuantity($quantity);
        if ($size === null) {
            return [
                'total' => 0,
                'breakdown' => null,
                'error' => '配送先に商品が5個以上含まれています。5個以上の配送はお問い合わせください。'
            ];
        }

        $area = $this->shippingAreaRepository->findByPrefId($Prefecture->getId());
        if (!$area) {
            return [
                'total' => 0,
                'breakdown' => null,
                'error' => '該当する配送エリアが見つかりません。都道府県: ' . $Prefecture->getName()
            ];
        }

        $rate = $this->shippingRateRepository->findByAreaIdAndSize($area->getId(), $size);
        if (!$rate) {
            return [
                'total' => 0,
                'breakdown' => null,
                'error' => '該当する送料設定が見つかりません。エリア: ' . $area->getName() . ', サイズ: ' . $size
            ];
        }

        $baseFee = (float)$rate->getRate();
        $coolFee = (float)$rate->getCoolFee();
        $boxFee = (float)$rate->getBoxFee();
        $total = $baseFee + $coolFee + $boxFee;

        return [
            'total' => $total,
            'breakdown' => [
                'area_name' => $area->getName(),
                'size' => $size,
                'quantity' => $quantity,
                'base_fee' => $baseFee,
                'cool_fee' => $coolFee,
                'box_fee' => $boxFee,
            ],
            'error' => null,
        ];
    }

    /**
     * @param \Eccube\Entity\Order $order
     * @param \Eccube\Entity\Shipping $shipping
     * @param array $breakdown
     * @return ShippingBreakdown
     */
    public function saveBreakdown($order, $shipping, array $breakdown): ?ShippingBreakdown
    {
        if (!$shipping->getId()) {
            return null;
        }

        $this->shippingBreakdownRepository->deleteByShippingId($shipping->getId());

        $entity = new ShippingBreakdown();
        $entity->setOrder($order);
        $entity->setShipping($shipping);
        $entity->setOrderId($order->getId());
        $entity->setShippingId($shipping->getId());
        $entity->setAreaName($breakdown['area_name']);
        $entity->setSize($breakdown['size']);
        $entity->setQuantity($breakdown['quantity']);
        $entity->setBaseFee((string)$breakdown['base_fee']);
        $entity->setCoolFee((string)$breakdown['cool_fee']);
        $entity->setBoxFee((string)$breakdown['box_fee']);
        $entity->setTotalFee((string)($breakdown['base_fee'] + $breakdown['cool_fee'] + $breakdown['box_fee']));

        return $entity;
    }

    /**
     * @param int $quantity
     * @return int|null
     */
    private function determineSizeByQuantity(int $quantity): ?int
    {
        $sizeConfig = $this->sizeConfigRepository->findByQuantity($quantity);

        if ($sizeConfig) {
            return $sizeConfig->getSize();
        }

        return null;
    }
}
