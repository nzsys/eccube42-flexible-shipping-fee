<?php

namespace Plugin\FlexibleShippingFee\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SizeConfig
 *
 * @ORM\Table(name="plg_flexible_shipping_size_config")
 * @ORM\Entity(repositoryClass="Plugin\FlexibleShippingFee\Repository\SizeConfigRepository")
 */
class SizeConfig
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
     * @ORM\Column(name="size", type="integer")
     */
    private $size;

    /**
     * @var int
     *
     * @ORM\Column(name="min_quantity", type="integer")
     */
    private $min_quantity;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_quantity", type="integer", nullable=true)
     */
    private $max_quantity;

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

    public function __construct()
    {
        $this->create_date = new \DateTime();
        $this->update_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMinQuantity(): ?int
    {
        return $this->min_quantity;
    }

    public function setMinQuantity(int $min_quantity): self
    {
        $this->min_quantity = $min_quantity;
        return $this;
    }

    public function getMaxQuantity(): ?int
    {
        return $this->max_quantity;
    }

    public function setMaxQuantity(?int $max_quantity): self
    {
        $this->max_quantity = $max_quantity;
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

    /**
     * 数量がこの設定範囲内かチェック
     */
    public function isInRange(int $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity !== null && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    /**
     * 表示用の数量範囲文字列を取得
     */
    public function getRangeLabel(): string
    {
        if ($this->max_quantity === null) {
            return $this->min_quantity . '個以上';
        }

        if ($this->min_quantity === $this->max_quantity) {
            return $this->min_quantity . '個';
        }

        return $this->min_quantity . '〜' . $this->max_quantity . '個';
    }
}
