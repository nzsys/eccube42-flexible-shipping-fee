<?php

namespace Plugin\FlexibleShippingFee\Service\PurchaseFlow\Processor;

use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\FlexibleShippingFee\Service\ShippingFeeService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Flexible Shipping Fee Calculator based on configurable area and rates
 */
class ShippingFeeCalculator implements ItemHolderPreprocessor
{
    /**
     * @var ShippingFeeService
     */
    private $shippingFeeService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ShippingFeeService $shippingFeeService,
        EntityManagerInterface $entityManager
    ) {
        $this->shippingFeeService = $shippingFeeService;
        $this->entityManager = $entityManager;
    }

    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!($itemHolder instanceof Order)) {
            return;
        }

        log_info('[FlexibleShippingFee] ShippingFeeCalculator::process() called');

        foreach ($itemHolder->getShippings() as $Shipping) {
            $Prefecture = $Shipping->getPrefecture();
            log_info('[FlexibleShippingFee] Shipping ID: ' . $Shipping->getId() . ', Prefecture: ' . ($Prefecture ? $Prefecture->getName() : 'null'));

            $result = $this->shippingFeeService->calculateShippingFee($Shipping);

            if ($result['error']) {
                log_error('[FlexibleShippingFee] Error: ' . $result['error']);
                $context->addError($result['error']);
                continue;
            }

            log_info('[FlexibleShippingFee] Calculated fee: ' . $result['total']);
            log_info('[FlexibleShippingFee] Breakdown: ' . json_encode($result['breakdown']));

            // Set shipping fee
            $Shipping->setShippingDeliveryFee($result['total']);

            // Save breakdown if this is an order (has ID)
            if ($itemHolder->getId() && $Shipping->getId() && $result['breakdown']) {
                $breakdown = $this->shippingFeeService->saveBreakdown(
                    $itemHolder,
                    $Shipping,
                    $result['breakdown']
                );
                $this->entityManager->persist($breakdown);
                $this->entityManager->flush();

                log_info('[FlexibleShippingFee] Breakdown saved for Order ID: ' . $itemHolder->getId() . ', Shipping ID: ' . $Shipping->getId());
            }
        }
    }
}
