<?php

namespace Plugin\FlexibleShippingFee\Tests\Service;

use Eccube\Entity\Shipping;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\OrderItem;
use Plugin\FlexibleShippingFee\Entity\ShippingArea;
use Plugin\FlexibleShippingFee\Entity\ShippingAreaPref;
use Plugin\FlexibleShippingFee\Entity\ShippingRate;
use Plugin\FlexibleShippingFee\Entity\SizeConfig;
use Plugin\FlexibleShippingFee\Repository\ShippingAreaRepository;
use Plugin\FlexibleShippingFee\Repository\ShippingRateRepository;
use Plugin\FlexibleShippingFee\Repository\ShippingBreakdownRepository;
use Plugin\FlexibleShippingFee\Repository\SizeConfigRepository;
use Plugin\FlexibleShippingFee\Service\ShippingFeeService;
use PHPUnit\Framework\TestCase;

class ShippingFeeServiceTest extends TestCase
{
    private $shippingFeeService;
    private $shippingAreaRepository;
    private $shippingRateRepository;
    private $shippingBreakdownRepository;
    private $sizeConfigRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shippingAreaRepository = $this->createMock(ShippingAreaRepository::class);
        $this->shippingRateRepository = $this->createMock(ShippingRateRepository::class);
        $this->shippingBreakdownRepository = $this->createMock(ShippingBreakdownRepository::class);
        $this->sizeConfigRepository = $this->createMock(SizeConfigRepository::class);

        $this->shippingFeeService = new ShippingFeeService(
            $this->shippingAreaRepository,
            $this->shippingRateRepository,
            $this->shippingBreakdownRepository,
            $this->sizeConfigRepository
        );
    }

    public function testCalculateShippingFeeWithoutPrefecture()
    {
        $shipping = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPref', 'getProductOrderItems'])
            ->getMock();
        $shipping->method('getPref')->willReturn(null);

        $result = $this->shippingFeeService->calculateShippingFee($shipping);

        $this->assertEquals(0, $result['total']);
        $this->assertNull($result['breakdown']);
        $this->assertNull($result['error']);
    }

    public function testCalculateShippingFeeWithQuantity2()
    {
        $prefecture = $this->createMock(Pref::class);
        $prefecture->method('getId')->willReturn(13); // Tokyo
        $prefecture->method('getName')->willReturn('東京都');

        $item1 = $this->createMock(OrderItem::class);
        $item1->method('getQuantity')->willReturn(1);
        $item1->method('getProductName')->willReturn('商品1');
        $item2 = $this->createMock(OrderItem::class);
        $item2->method('getQuantity')->willReturn(1);
        $item2->method('getProductName')->willReturn('商品2');

        $shipping = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPref', 'getProductOrderItems'])
            ->getMock();
        $shipping->method('getPref')->willReturn($prefecture);
        $shipping->method('getProductOrderItems')->willReturn([$item1, $item2]);

        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(60);
        $sizeConfig->setMinQuantity(1);
        $sizeConfig->setMaxQuantity(2);

        $this->sizeConfigRepository
            ->method('findByQuantity')
            ->with(2)
            ->willReturn($sizeConfig);

        $area = new ShippingArea();
        $area->setName('関東');
        $reflection = new \ReflectionClass($area);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($area, 1);

        $this->shippingAreaRepository
            ->method('findByPrefId')
            ->with(13)
            ->willReturn($area);

        $rate = new ShippingRate();
        $rate->setSize(60);
        $rate->setRate('814');
        $rate->setCoolFee('275');
        $rate->setBoxFee('107');

        $this->shippingRateRepository
            ->method('findByAreaIdAndSize')
            ->with(1, 60)
            ->willReturn($rate);

        $result = $this->shippingFeeService->calculateShippingFee($shipping);

        $this->assertEquals(1196.0, $result['total']); // 814 + 275 + 107
        $this->assertIsArray($result['breakdown']);
        $this->assertEquals('関東', $result['breakdown']['area_name']);
        $this->assertEquals(60, $result['breakdown']['size']);
        $this->assertEquals('1〜2個', $result['breakdown']['size_range']);
        $this->assertEquals(2, $result['breakdown']['quantity']);
        $this->assertNull($result['error']);
    }

    public function testCalculateShippingFeeWithQuantity4()
    {
        $prefecture = $this->createMock(Pref::class);
        $prefecture->method('getId')->willReturn(13);
        $prefecture->method('getName')->willReturn('東京都');

        $items = [];
        for ($i = 0; $i < 4; $i++) {
            $item = $this->createMock(OrderItem::class);
            $item->method('getQuantity')->willReturn(1);
            $item->method('getProductName')->willReturn('商品' . ($i + 1));
            $items[] = $item;
        }

        $shipping = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPref', 'getProductOrderItems'])
            ->getMock();
        $shipping->method('getPref')->willReturn($prefecture);
        $shipping->method('getProductOrderItems')->willReturn($items);

        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(80);
        $sizeConfig->setMinQuantity(3);
        $sizeConfig->setMaxQuantity(4);

        $this->sizeConfigRepository
            ->method('findByQuantity')
            ->with(4)
            ->willReturn($sizeConfig);

        $area = new ShippingArea();
        $area->setName('関東');
        $reflection = new \ReflectionClass($area);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($area, 1);

        $this->shippingAreaRepository
            ->method('findByPrefId')
            ->with(13)
            ->willReturn($area);

        $rate = new ShippingRate();
        $rate->setSize(80);
        $rate->setRate('874');
        $rate->setCoolFee('330');
        $rate->setBoxFee('151');

        $this->shippingRateRepository
            ->method('findByAreaIdAndSize')
            ->with(1, 80)
            ->willReturn($rate);

        $result = $this->shippingFeeService->calculateShippingFee($shipping);

        $this->assertEquals(1355.0, $result['total']); // 874 + 330 + 151
        $this->assertEquals(80, $result['breakdown']['size']);
        $this->assertEquals('3〜4個', $result['breakdown']['size_range']);
        $this->assertEquals(4, $result['breakdown']['quantity']);
    }

    public function testCalculateShippingFeeWithQuantityOver5()
    {
        $prefecture = $this->createMock(Pref::class);
        $prefecture->method('getId')->willReturn(13);

        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $item = $this->createMock(OrderItem::class);
            $item->method('getQuantity')->willReturn(1);
            $item->method('getProductName')->willReturn('商品' . ($i + 1));
            $items[] = $item;
        }

        $shipping = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPref', 'getProductOrderItems'])
            ->getMock();
        $shipping->method('getPref')->willReturn($prefecture);
        $shipping->method('getProductOrderItems')->willReturn($items);

        $this->sizeConfigRepository
            ->method('findByQuantity')
            ->with(5)
            ->willReturn(null);

        $result = $this->shippingFeeService->calculateShippingFee($shipping);

        $this->assertEquals(0, $result['total']);
        $this->assertNull($result['breakdown']);
        $this->assertStringContainsString('5個以上', $result['error']);
    }

    public function testCalculateShippingFeeWithNoArea()
    {
        $prefecture = $this->createMock(Pref::class);
        $prefecture->method('getId')->willReturn(99); // Non-existent
        $prefecture->method('getName')->willReturn('テスト県');

        $item = $this->createMock(OrderItem::class);
        $item->method('getQuantity')->willReturn(1);
        $item->method('getProductName')->willReturn('商品1');

        $shipping = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPref', 'getProductOrderItems'])
            ->getMock();
        $shipping->method('getPref')->willReturn($prefecture);
        $shipping->method('getProductOrderItems')->willReturn([$item]);

        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(60);
        $sizeConfig->setMinQuantity(1);
        $sizeConfig->setMaxQuantity(2);

        $this->sizeConfigRepository
            ->method('findByQuantity')
            ->with(1)
            ->willReturn($sizeConfig);

        $this->shippingAreaRepository
            ->method('findByPrefId')
            ->with(99)
            ->willReturn(null);

        $result = $this->shippingFeeService->calculateShippingFee($shipping);

        $this->assertEquals(0, $result['total']);
        $this->assertStringContainsString('配送エリアが見つかりません', $result['error']);
    }

    public function testSaveBreakdown()
    {
        $breakdown = [
            'area_name' => '関東',
            'size' => 60,
            'quantity' => 2,
            'base_fee' => 814.0,
            'cool_fee' => 275.0,
            'box_fee' => 107.0,
        ];

        $order = $this->createMock(\Eccube\Entity\Order::class);
        $order->method('getId')->willReturn(100);

        $shipping = $this->createMock(\Eccube\Entity\Shipping::class);
        $shipping->method('getId')->willReturn(1);

        $this->shippingBreakdownRepository
            ->expects($this->once())
            ->method('deleteByShippingId')
            ->with(1);

        $entity = $this->shippingFeeService->saveBreakdown($order, $shipping, $breakdown);

        $this->assertNotNull($entity);
        $this->assertEquals(100, $entity->getOrderId());
        $this->assertEquals(1, $entity->getShippingId());
        $this->assertEquals('関東', $entity->getAreaName());
        $this->assertEquals(60, $entity->getSize());
        $this->assertEquals(2, $entity->getQuantity());
        $this->assertEquals('814', $entity->getBaseFee());
        $this->assertEquals('275', $entity->getCoolFee());
        $this->assertEquals('107', $entity->getBoxFee());
        $this->assertEquals('1196', $entity->getTotalFee());
    }

    public function testSaveBreakdownWithNullShippingId()
    {
        $breakdown = [
            'area_name' => '関東',
            'size' => 60,
            'quantity' => 2,
            'base_fee' => 814.0,
            'cool_fee' => 275.0,
            'box_fee' => 107.0,
        ];

        $order = $this->createMock(\Eccube\Entity\Order::class);
        $order->method('getId')->willReturn(100);

        $shipping = $this->createMock(\Eccube\Entity\Shipping::class);
        $shipping->method('getId')->willReturn(null);

        $this->shippingBreakdownRepository
            ->expects($this->never())
            ->method('deleteByShippingId');

        $entity = $this->shippingFeeService->saveBreakdown($order, $shipping, $breakdown);

        $this->assertNull($entity);
    }
}
