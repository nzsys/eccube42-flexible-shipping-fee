<?php

namespace Plugin\FlexibleShippingFee\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Table(name="plg_flexible_shipping_area")
 * @ORM\Entity(repositoryClass="Plugin\FlexibleShippingFee\Repository\ShippingAreaRepository")
 */
class ShippingArea
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer", options={"default":0})
     */
    private $sort_no = 0;

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
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\FlexibleShippingFee\Entity\ShippingAreaPref", mappedBy="ShippingArea", cascade={"persist", "remove"})
     */
    private $ShippingAreaPrefs;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\FlexibleShippingFee\Entity\ShippingRate", mappedBy="ShippingArea", cascade={"persist", "remove"})
     */
    private $ShippingRates;

    public function __construct()
    {
        $this->ShippingAreaPrefs = new ArrayCollection();
        $this->ShippingRates = new ArrayCollection();
        $this->create_date = new \DateTime();
        $this->update_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSortNo(): ?int
    {
        return $this->sort_no;
    }

    public function setSortNo(int $sort_no): self
    {
        $this->sort_no = $sort_no;
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

    public function getShippingAreaPrefs(): Collection
    {
        return $this->ShippingAreaPrefs;
    }

    public function addShippingAreaPref(ShippingAreaPref $shippingAreaPref): self
    {
        if (!$this->ShippingAreaPrefs->contains($shippingAreaPref)) {
            $this->ShippingAreaPrefs[] = $shippingAreaPref;
            $shippingAreaPref->setShippingArea($this);
        }
        return $this;
    }

    public function removeShippingAreaPref(ShippingAreaPref $shippingAreaPref): self
    {
        if ($this->ShippingAreaPrefs->removeElement($shippingAreaPref)) {
            if ($shippingAreaPref->getShippingArea() === $this) {
                $shippingAreaPref->setShippingArea(null);
            }
        }
        return $this;
    }

    public function getShippingRates(): Collection
    {
        return $this->ShippingRates;
    }

    public function addShippingRate(ShippingRate $shippingRate): self
    {
        if (!$this->ShippingRates->contains($shippingRate)) {
            $this->ShippingRates[] = $shippingRate;
            $shippingRate->setShippingArea($this);
        }
        return $this;
    }

    public function removeShippingRate(ShippingRate $shippingRate): self
    {
        if ($this->ShippingRates->removeElement($shippingRate)) {
            if ($shippingRate->getShippingArea() === $this) {
                $shippingRate->setShippingArea(null);
            }
        }
        return $this;
    }
}
