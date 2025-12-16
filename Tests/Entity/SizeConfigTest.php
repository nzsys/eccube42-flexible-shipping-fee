<?php

namespace Plugin\FlexibleShippingFee\Tests\Entity;

use Plugin\FlexibleShippingFee\Entity\SizeConfig;
use PHPUnit\Framework\TestCase;

class SizeConfigTest extends TestCase
{
    public function testIsInRangeWithinRange()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(60);
        $sizeConfig->setMinQuantity(1);
        $sizeConfig->setMaxQuantity(2);

        $this->assertTrue($sizeConfig->isInRange(1));
        $this->assertTrue($sizeConfig->isInRange(2));
    }

    public function testIsInRangeBelowMin()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(60);
        $sizeConfig->setMinQuantity(1);
        $sizeConfig->setMaxQuantity(2);

        $this->assertFalse($sizeConfig->isInRange(0));
    }

    public function testIsInRangeAboveMax()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(60);
        $sizeConfig->setMinQuantity(1);
        $sizeConfig->setMaxQuantity(2);

        $this->assertFalse($sizeConfig->isInRange(3));
    }

    public function testIsInRangeWithNullMaxQuantity()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(100);
        $sizeConfig->setMinQuantity(5);
        $sizeConfig->setMaxQuantity(null);

        $this->assertTrue($sizeConfig->isInRange(5));
        $this->assertTrue($sizeConfig->isInRange(10));
        $this->assertTrue($sizeConfig->isInRange(100));
        $this->assertFalse($sizeConfig->isInRange(4));
    }

    public function testGetRangeLabelWithMinAndMax()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(60);
        $sizeConfig->setMinQuantity(1);
        $sizeConfig->setMaxQuantity(2);

        $this->assertEquals('1〜2個', $sizeConfig->getRangeLabel());
    }

    public function testGetRangeLabelWithSameMinMax()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(60);
        $sizeConfig->setMinQuantity(3);
        $sizeConfig->setMaxQuantity(3);

        $this->assertEquals('3個', $sizeConfig->getRangeLabel());
    }

    public function testGetRangeLabelWithNullMax()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(100);
        $sizeConfig->setMinQuantity(5);
        $sizeConfig->setMaxQuantity(null);

        $this->assertEquals('5個以上', $sizeConfig->getRangeLabel());
    }

    public function testGetRangeLabelForSize80()
    {
        $sizeConfig = new SizeConfig();
        $sizeConfig->setSize(80);
        $sizeConfig->setMinQuantity(3);
        $sizeConfig->setMaxQuantity(4);

        $this->assertEquals('3〜4個', $sizeConfig->getRangeLabel());
    }

    public function testSettersAndGetters()
    {
        $sizeConfig = new SizeConfig();

        $sizeConfig->setSize(60);
        $this->assertEquals(60, $sizeConfig->getSize());

        $sizeConfig->setMinQuantity(1);
        $this->assertEquals(1, $sizeConfig->getMinQuantity());

        $sizeConfig->setMaxQuantity(2);
        $this->assertEquals(2, $sizeConfig->getMaxQuantity());

        $sizeConfig->setSortNo(10);
        $this->assertEquals(10, $sizeConfig->getSortNo());
    }

    public function testCreateDateIsSetInConstructor()
    {
        $sizeConfig = new SizeConfig();

        $this->assertInstanceOf(\DateTime::class, $sizeConfig->getCreateDate());
        $this->assertInstanceOf(\DateTime::class, $sizeConfig->getUpdateDate());
    }
}
