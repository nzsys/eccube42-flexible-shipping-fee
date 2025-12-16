<?php

namespace Plugin\FlexibleShippingFee\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;

/**
 * @ORM\Table(name="plg_flexible_shipping_breakdown")
 * @ORM\Entity(repositoryClass="Plugin\FlexibleShippingFee\Repository\ShippingBreakdownRepository")
 */
class ShippingBreakdown
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
     * @ORM\Column(name="order_id", type="integer")
     */
    private $order_id;

    /**
     * @var int
     *
     * @ORM\Column(name="shipping_id", type="integer")
     */
    private $shipping_id;

    /**
     * @var string
     *
     * @ORM\Column(name="area_name", type="string", length=255)
     */
    private $area_name;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="integer")
     */
    private $size;

    /**
     * @var int
     *
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;

    /**
     * @var string
     *
     * @ORM\Column(name="base_fee", type="decimal", precision=12, scale=2, options={"default":0})
     */
    private $base_fee = '0.00';

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
     * @var string
     *
     * @ORM\Column(name="total_fee", type="decimal", precision=12, scale=2, options={"default":0})
     */
    private $total_fee = '0.00';

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
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $Order;

    /**
     * @var Shipping
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Shipping")
     * @ORM\JoinColumn(name="shipping_id", referencedColumnName="id")
     */
    private $Shipping;

    public function __construct()
    {
        $this->create_date = new \DateTime();
        $this->update_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?int
    {
        return $this->order_id;
    }

    public function setOrderId(int $order_id): self
    {
        $this->order_id = $order_id;
        return $this;
    }

    public function getShippingId(): ?int
    {
        return $this->shipping_id;
    }

    public function setShippingId(int $shipping_id): self
    {
        $this->shipping_id = $shipping_id;
        return $this;
    }

    public function getAreaName(): ?string
    {
        return $this->area_name;
    }

    public function setAreaName(string $area_name): self
    {
        $this->area_name = $area_name;
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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getBaseFee(): ?string
    {
        return $this->base_fee;
    }

    public function setBaseFee(string $base_fee): self
    {
        $this->base_fee = $base_fee;
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

    public function getTotalFee(): ?string
    {
        return $this->total_fee;
    }

    public function setTotalFee(string $total_fee): self
    {
        $this->total_fee = $total_fee;
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

    public function getOrder(): ?Order
    {
        return $this->Order;
    }

    public function setOrder(?Order $order): self
    {
        $this->Order = $order;
        if ($order) {
            $this->order_id = $order->getId();
        }
        return $this;
    }

    public function getShipping(): ?Shipping
    {
        return $this->Shipping;
    }

    public function setShipping(?Shipping $shipping): self
    {
        $this->Shipping = $shipping;
        if ($shipping) {
            $this->shipping_id = $shipping->getId();
        }
        return $this;
    }
}
