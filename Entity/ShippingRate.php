<?php

namespace Plugin\FlexibleShippingFee\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShippingRate
 *
 * @ORM\Table(name="plg_flexible_shipping_rate", uniqueConstraints={@ORM\UniqueConstraint(name="unique_area_size", columns={"area_id", "size"})})
 * @ORM\Entity(repositoryClass="Plugin\FlexibleShippingFee\Repository\ShippingRateRepository")
 */
class ShippingRate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":false})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="area_id", type="integer")
     */
    private $area_id;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="integer")
     */
    private $size;

    /**
     * @var string
     *
     * @ORM\Column(name="rate", type="decimal", precision=12, scale=2, options={"default":0})
     */
    private $rate = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="cool_fee", type="decimal", precision=12, scale=2, options={"default":0})
     */
    private $cool_fee = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="box_fee", type="decimal", precision=12, scale=2, options={"default":0})
     */
    private $box_fee = '0.00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetime")
     */
    private $update_date;

    /**
     * @var ShippingArea
     *
     * @ORM\ManyToOne(targetEntity="Plugin\FlexibleShippingFee\Entity\ShippingArea", inversedBy="ShippingRates")
     * @ORM\JoinColumn(name="area_id", referencedColumnName="id")
     */
    private $ShippingArea;

    public function __construct()
    {
        $this->create_date = new \DateTime();
        $this->update_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAreaId(): ?int
    {
        return $this->area_id;
    }

    public function setAreaId(int $area_id): self
    {
        $this->area_id = $area_id;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function setRate(string $rate): self
    {
        $this->rate = $rate;
        return $this;
    }

    public function getCoolFee(): ?string
    {
        return $this->cool_fee;
    }

    public function setCoolFee(string $cool_fee): self
    {
        $this->cool_fee = $cool_fee;
        return $this;
    }

    public function getBoxFee(): ?string
    {
        return $this->box_fee;
    }

    public function setBoxFee(string $box_fee): self
    {
        $this->box_fee = $box_fee;
        return $this;
    }

    public function getCreateDate(): ?\DateTime
    {
        return $this->create_date;
    }

    public function setCreateDate(\DateTime $create_date): self
    {
        $this->create_date = $create_date;
        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->update_date;
    }

    public function setUpdateDate(\DateTime $update_date): self
    {
        $this->update_date = $update_date;
        return $this;
    }

    public function getShippingArea(): ?ShippingArea
    {
        return $this->ShippingArea;
    }

    public function setShippingArea(?ShippingArea $shippingArea): self
    {
        $this->ShippingArea = $shippingArea;
        if ($shippingArea) {
            $this->area_id = $shippingArea->getId();
        }
        return $this;
    }
}
