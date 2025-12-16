<?php

namespace Plugin\FlexibleShippingFee\Service\PurchaseFlow\Processor;

use Eccube\Annotation\ShoppingFlow;
use Eccube\Annotation\CartFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Cart;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Entity\Shipping;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\TaxRuleService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Plugin\FlexibleShippingFee\Service\ShippingFeeService;

/**
 * @ShoppingFlow()
 * @CartFlow()
 */
class ShippingCalculator implements ItemHolderPreprocessor
{
    /** @var ShippingFeeService */
    private $shippingFeeService;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var TaxRuleRepository */
    private $taxRuleRepository;

    /** @var TaxRuleService */
    private $taxRuleService;

    /** @var SessionInterface */
    private $session;

    public function __construct(
        ShippingFeeService $shippingFeeService,
        EntityManagerInterface $entityManager,
        TaxRuleRepository $taxRuleRepository,
        TaxRuleService $taxRuleService,
        SessionInterface $session
    ) {
        $this->shippingFeeService = $shippingFeeService;
        $this->entityManager = $entityManager;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxRuleService = $taxRuleService;
        $this->session = $session;
    }

    public function process(
        ItemHolderInterface $itemHolder,
        PurchaseContext $context
    ): void {
        log_info('[FlexibleShippingFee] ShippingCalculator::process() が呼ばれました - ' . get_class($itemHolder));

        if (!$itemHolder instanceof Order) {
            log_info('[FlexibleShippingFee] Cart detected, skipping (no shippings in cart)');
            return;
        }

        $this->removeDeliveryFeeItem($itemHolder);

        foreach ($itemHolder->getShippings() as $Shipping) {
            $prefId = $Shipping->getPref() ? $Shipping->getPref()->getId() : 'null';
            log_info('[FlexibleShippingFee] 配送先ID: ' . ($Shipping->getId() ?? 'null') . ', 都道府県ID: ' . $prefId);
            $this->calculateShippingFee($Shipping, $context, $itemHolder);
        }
    }

    private function removeDeliveryFeeItem(
        Order $Order
    ): void {
        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $item) {
                if ($item->isDeliveryFee()) {
                    $Shipping->removeOrderItem($item);
                    $Order->removeOrderItem($item);
                    $this->entityManager->remove($item);
                }
            }
        }
    }

    private function calculateShippingFee(
        Shipping $Shipping,
        PurchaseContext $context,
        ItemHolderInterface $itemHolder
    ): void {
        $result = $this->shippingFeeService->calculateShippingFee($Shipping);

        if ($result['error']) {
            log_error('[FlexibleShippingFee] エラー: ' . $result['error']);
            $this->session->getFlashBag()->add('eccube.front.error', $result['error']);
            return;
        }

        $totalShippingFee = $result['total'];

        $DeliveryFeeType = $this->entityManager->find(OrderItemType::class, OrderItemType::DELIVERY_FEE);
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        $Taxation = $this->entityManager->find(TaxType::class, TaxType::TAXATION);

        $TaxRule = $this->taxRuleRepository->getByRule();

        $OrderItem = new OrderItem();
        $OrderItem->setProductName($DeliveryFeeType->getName())
            ->setPrice($totalShippingFee)
            ->setQuantity(1)
            ->setOrderItemType($DeliveryFeeType)
            ->setShipping($Shipping)
            ->setOrder($itemHolder)
            ->setTaxDisplayType($TaxInclude)
            ->setTaxType($Taxation)
            ->setTaxRate($TaxRule->getTaxRate())
            ->setTaxAdjust($TaxRule->getTaxAdjust())
            ->setRoundingType($TaxRule->getRoundingType())
            ->setProcessorName(self::class);

        $tax = $this->taxRuleService->calcTaxIncluded(
            $OrderItem->getPrice(),
            $OrderItem->getTaxRate(),
            $OrderItem->getRoundingType()->getId(),
            $OrderItem->getTaxAdjust()
        );
        $OrderItem->setTax($tax);

        $itemHolder->addItem($OrderItem);
        $Shipping->addOrderItem($OrderItem);

        if ($itemHolder instanceof Order && $result['breakdown']) {
            $breakdown = $result['breakdown'];

            $breakdownEntity = $this->shippingFeeService->saveBreakdown(
                $itemHolder,
                $Shipping,
                $breakdown
            );

            if ($breakdownEntity) {
                $this->entityManager->persist($breakdownEntity);
                $this->entityManager->flush();
            }
        }
    }
}
