<?php

namespace Plugin\FlexibleShippingFee\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\Pref;

/**
 * @ORM\Table(name="plg_flexible_shipping_area_pref", uniqueConstraints={@ORM\UniqueConstraint(name="unique_area_pref", columns={"area_id", "pref_id"})})
 * @ORM\Entity(repositoryClass="Plugin\FlexibleShippingFee\Repository\ShippingAreaPrefRepository")
 */
class ShippingAreaPref
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
     * @ORM\Column(name="pref_id", type="integer")
     */
    private $pref_id;

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
     * @ORM\ManyToOne(targetEntity="Plugin\FlexibleShippingFee\Entity\ShippingArea", inversedBy="ShippingAreaPrefs")
     * @ORM\JoinColumn(name="area_id", referencedColumnName="id")
     */
    private $ShippingArea;

    /**
     * @var Pref
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Pref")
     * @ORM\JoinColumn(name="pref_id", referencedColumnName="id")
     */
    private $Pref;

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

    public function getPrefId(): ?int
    {
        return $this->pref_id;
    }

    public function setPrefId(int $pref_id): self
    {
        $this->pref_id = $pref_id;
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

    public function getPref(): ?Pref
    {
        return $this->Pref;
    }

    public function setPref(?Pref $pref): self
    {
        $this->Pref = $pref;
        if ($pref) {
            $this->pref_id = $pref->getId();
        }
        return $this;
    }
}
