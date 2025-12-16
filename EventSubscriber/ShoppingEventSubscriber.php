<?php

namespace Plugin\FlexibleShippingFee\EventSubscriber;

use Eccube\Event\TemplateEvent;
use Eccube\Repository\OrderRepository;
use Plugin\FlexibleShippingFee\Repository\ShippingBreakdownRepository;
use Plugin\FlexibleShippingFee\Repository\SizeConfigRepository;
use Plugin\FlexibleShippingFee\Service\ShippingFeeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShoppingEventSubscriber implements EventSubscriberInterface
{
    /** @var ShippingBreakdownRepository */
    private $shippingBreakdownRepository;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var ShippingFeeService */
    private $shippingFeeService;

    /** @var SizeConfigRepository */
    private $sizeConfigRepository;

    public function __construct(
        ShippingBreakdownRepository $shippingBreakdownRepository,
        OrderRepository $orderRepository,
        ShippingFeeService $shippingFeeService,
        SizeConfigRepository $sizeConfigRepository
    ) {
        $this->shippingBreakdownRepository = $shippingBreakdownRepository;
        $this->orderRepository = $orderRepository;
        $this->shippingFeeService = $shippingFeeService;
        $this->sizeConfigRepository = $sizeConfigRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'onShopping',
            'Shopping/confirm.twig' => 'onShoppingConfirm',
        ];
    }

    public function onShopping(TemplateEvent $event): void
    {
        $parameters = $event->getParameters();

        if (!isset($parameters['Order'])) {
            return;
        }

        $Order = $parameters['Order'];
        $breakdowns = [];

        foreach ($Order->getShippings() as $Shipping) {
            $result = $this->shippingFeeService->calculateShippingFee($Shipping);

            if ($result['breakdown']) {
                $breakdownData = (object) [
                    'areaName' => $result['breakdown']['area_name'],
                    'size' => $result['breakdown']['size'],
                    'sizeRange' => $result['breakdown']['size_range'],
                    'quantity' => $result['breakdown']['quantity'],
                    'baseFee' => $result['breakdown']['base_fee'],
                    'coolFee' => $result['breakdown']['cool_fee'],
                    'boxFee' => $result['breakdown']['box_fee'],
                ];
                $breakdowns[$Shipping->getId() ?? 0] = $breakdownData;
            }
        }

        $parameters['shipping_breakdowns'] = $breakdowns;
        $event->setParameters($parameters);

        $snippet = $this->getTemplateSnippet();
        $event->addSnippet($snippet, false);
    }

    public function onShoppingConfirm(TemplateEvent $event): void
    {
        $parameters = $event->getParameters();

        if (!isset($parameters['Order'])) {
            return;
        }

        $Order = $parameters['Order'];
        $breakdowns = [];

        foreach ($Order->getShippings() as $Shipping) {
            if ($Shipping->getId()) {
                $breakdown = $this->shippingBreakdownRepository->findOneBy(['shipping_id' => $Shipping->getId()]);
                if ($breakdown) {
                    $sizeConfig = $this->sizeConfigRepository->findOneBy(['size' => $breakdown->getSize()]);
                    $sizeRange = $sizeConfig ? $sizeConfig->getRangeLabel() : '';

                    $breakdownData = (object) [
                        'areaName' => $breakdown->getAreaName(),
                        'size' => $breakdown->getSize(),
                        'sizeRange' => $sizeRange,
                        'quantity' => $breakdown->getQuantity(),
                        'baseFee' => $breakdown->getBaseFee(),
                        'coolFee' => $breakdown->getCoolFee(),
                        'boxFee' => $breakdown->getBoxFee(),
                    ];
                    $breakdowns[$Shipping->getId()] = $breakdownData;
                }
            }
        }

        $parameters['shipping_breakdowns'] = $breakdowns;
        $event->setParameters($parameters);

        $snippet = $this->getTemplateSnippet();
        $event->addSnippet($snippet, false);
    }

    private function getTemplateSnippet(): string
    {
        return <<<'TWIG'
<style>
.shipping-breakdown {
    font-size: 0.85rem;
    color: #666;
    margin: 0.5rem 0 0 0;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-left: 3px solid #007bff;
}
.shipping-breakdown dt {
    display: inline-block;
    width: 110px;
    font-weight: normal;
}
.shipping-breakdown dd {
    display: inline-block;
    margin: 0;
}
</style>

<script>
$(document).ready(function() {
    // Check if there are any errors in the page
    var hasErrors = $('.ec-alert-warning').length > 0 || $('.alert-danger').length > 0;

    if (hasErrors) {
        // Disable order button
        $('button[type="submit"]').prop('disabled', true).addClass('disabled');
        $('button[type="submit"]').after('<p class="text-danger mt-2">エラーがあるため注文できません。カートに戻って商品数量を調整してください。</p>');
    }
});
</script>

{% if shipping_breakdowns is defined and shipping_breakdowns|length > 0 %}
    <script>
    $(document).ready(function() {
        {% for shippingId, breakdown in shipping_breakdowns %}
            var breakdownHtml = '<dl class="shipping-breakdown">' +
                '<dt>エリア:</dt><dd>{{ breakdown.areaName }}</dd><br>' +
                '<dt>箱サイズ:</dt><dd>{{ breakdown.size }}サイズの箱 ({{ breakdown.sizeRange }})</dd><br>' +
                '<dt>基本送料:</dt><dd>{{ breakdown.baseFee|price }}</dd><br>' +
                '<dt>クール便料金:</dt><dd>{{ breakdown.coolFee|price }}</dd><br>' +
                '<dt>箱代:</dt><dd>{{ breakdown.boxFee|price }}</dd>' +
                '</dl>';

            // Find the shipping fee row and add breakdown after it
            $('.ec-totalBox__spec:contains("送料")').after(breakdownHtml);
        {% endfor %}
    });
    </script>
{% endif %}
TWIG;
    }
}
