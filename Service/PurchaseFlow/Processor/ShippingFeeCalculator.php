<?php

namespace Plugin\FlexibleShippingFee\Service\PurchaseFlow\Processor;

use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\FlexibleShippingFee\Service\ShippingFeeService;
use Doctrine\ORM\EntityManagerInterface;

class ShippingFeeCalculator implements ItemHolderPreprocessor
{
    /** @var ShippingFeeService */
    private $shippingFeeService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ShippingFeeService $shippingFeeService,
        EntityManagerInterface $entityManager
    ) {
        $this->shippingFeeService = $shippingFeeService;
        $this->entityManager = $entityManager;
    }

    public function process(
        ItemHolderInterface $itemHolder,
        PurchaseContext $context
    ): void {
        if (!($itemHolder instanceof Order)) {
            return;
        }

        foreach ($itemHolder->getShippings() as $Shipping) {
            $Prefecture = $Shipping->getPrefecture();
            $result = $this->shippingFeeService->calculateShippingFee($Shipping);

            if ($result['error']) {
                log_error('[FlexibleShippingFee] Error: ' . $result['error']);
                $context->addError($result['error']);
                continue;
            }

            $Shipping->setShippingDeliveryFee($result['total']);

            if ($itemHolder->getId() && $Shipping->getId() && $result['breakdown']) {
                $breakdown = $this->shippingFeeService->saveBreakdown(
                    $itemHolder,
                    $Shipping,
                    $result['breakdown']
                );
                $this->entityManager->persist($breakdown);
                $this->entityManager->flush();
            }
        }
    }
}
