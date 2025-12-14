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
 * Flexible Shipping Fee Calculator using database configuration.
 *
 * @ShoppingFlow()
 * @CartFlow()
 */
class ShippingCalculator implements ItemHolderPreprocessor
{
    /**
     * @var ShippingFeeService
     */
    private $shippingFeeService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    /**
     * @var TaxRuleService
     */
    private $taxRuleService;

    /**
     * @var SessionInterface
     */
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

    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        log_info('[FlexibleShippingFee] ShippingCalculator::process() が呼ばれました - ' . get_class($itemHolder));

        // Cart doesn't have Shippings, only process Order
        if (!$itemHolder instanceof Order) {
            log_info('[FlexibleShippingFee] Cart detected, skipping (no shippings in cart)');
            return;
        }

        // First, remove existing FlexibleShippingFee delivery fee items
        $this->removeDeliveryFeeItem($itemHolder);

        foreach ($itemHolder->getShippings() as $Shipping) {
            $prefId = $Shipping->getPref() ? $Shipping->getPref()->getId() : 'null';
            log_info('[FlexibleShippingFee] 配送先ID: ' . ($Shipping->getId() ?? 'null') . ', 都道府県ID: ' . $prefId);
            $this->calculateShippingFee($Shipping, $context, $itemHolder);
        }
    }

    /**
     * Remove existing delivery fee items (both from EC-CUBE core and this plugin)
     */
    private function removeDeliveryFeeItem(Order $Order)
    {
        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $item) {
                // Remove all delivery fee items (isDeliveryFee() checks OrderItemType)
                if ($item->isDeliveryFee()) {
                    $Shipping->removeOrderItem($item);
                    $Order->removeOrderItem($item);
                    $this->entityManager->remove($item);
                }
            }
        }
    }

    private function calculateShippingFee(Shipping $Shipping, PurchaseContext $context, ItemHolderInterface $itemHolder)
    {
        // Calculate shipping fee using ShippingFeeService
        $result = $this->shippingFeeService->calculateShippingFee($Shipping);

        // Check for errors
        if ($result['error']) {
            log_error('[FlexibleShippingFee] エラー: ' . $result['error']);

            // Add error message to session flash bag
            $this->session->getFlashBag()->add('eccube.front.error', $result['error']);

            // Set shipping fee to 0 and continue (don't create OrderItem)
            return;
        }

        // Get total shipping fee
        $totalShippingFee = $result['total'];

        // Create OrderItem for shipping fee
        $DeliveryFeeType = $this->entityManager->find(OrderItemType::class, OrderItemType::DELIVERY_FEE);
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        $Taxation = $this->entityManager->find(TaxType::class, TaxType::TAXATION);

        // Get tax rule for delivery fee (non-product items use default tax rule)
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

        // Calculate tax (税込 = INCLUDED)
        $tax = $this->taxRuleService->calcTaxIncluded(
            $OrderItem->getPrice(),
            $OrderItem->getTaxRate(),
            $OrderItem->getRoundingType()->getId(),
            $OrderItem->getTaxAdjust()
        );
        $OrderItem->setTax($tax);

        $itemHolder->addItem($OrderItem);
        $Shipping->addOrderItem($OrderItem);

        // Save breakdown for Order (not for Cart)
        if ($itemHolder instanceof Order && $result['breakdown']) {
            $breakdown = $result['breakdown'];

            log_info('[FlexibleShippingFee] エリア: ' . $breakdown['area_name'] .
                     ', サイズ: ' . $breakdown['size'] .
                     ', 数量: ' . $breakdown['quantity'] .
                     ', 基本送料: ' . $breakdown['base_fee'] .
                     ', クール便: ' . $breakdown['cool_fee'] .
                     ', 箱代: ' . $breakdown['box_fee'] .
                     ', 合計: ' . $totalShippingFee);

            // Save breakdown to database (only if shipping has ID)
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
