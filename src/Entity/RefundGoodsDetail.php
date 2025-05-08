<?php

namespace WechatPayBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use WechatPayBundle\Repository\RefundGoodsDetailRepository;

#[ORM\Entity(repositoryClass: RefundGoodsDetailRepository::class)]
#[ORM\Table(name: 'wechat_refund_goods_detail', options: ['comment' => '退款订单-商品明细'])]
class RefundGoodsDetail
{
    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[ORM\ManyToOne(inversedBy: 'goodsDetails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RefundOrder $refundOrder = null;

    #[ORM\Column(length: 32, options: ['comment' => '商户侧商品编码'])]
    private ?string $merchantGoodsId = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '微信支付商品编码'])]
    private ?string $wechatpayGoodsId = null;

    #[ORM\Column(length: 256, nullable: true, options: ['comment' => '商品名称'])]
    private ?string $goodsName = null;

    #[ORM\Column(options: ['comment' => '商品单价'])]
    private ?int $unitPrice = null;

    #[ORM\Column(options: ['comment' => '商品退款金额'])]
    private ?int $refundAmount = null;

    #[ORM\Column(options: ['comment' => '商品退货数量'])]
    private ?int $refundQuantity = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getRefundOrder(): ?RefundOrder
    {
        return $this->refundOrder;
    }

    public function setRefundOrder(?RefundOrder $refundOrder): static
    {
        $this->refundOrder = $refundOrder;

        return $this;
    }

    public function getMerchantGoodsId(): ?string
    {
        return $this->merchantGoodsId;
    }

    public function setMerchantGoodsId(string $merchantGoodsId): static
    {
        $this->merchantGoodsId = $merchantGoodsId;

        return $this;
    }

    public function getWechatpayGoodsId(): ?string
    {
        return $this->wechatpayGoodsId;
    }

    public function setWechatpayGoodsId(?string $wechatpayGoodsId): static
    {
        $this->wechatpayGoodsId = $wechatpayGoodsId;

        return $this;
    }

    public function getGoodsName(): ?string
    {
        return $this->goodsName;
    }

    public function setGoodsName(?string $goodsName): static
    {
        $this->goodsName = $goodsName;

        return $this;
    }

    public function getUnitPrice(): ?int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getRefundAmount(): ?int
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(int $refundAmount): static
    {
        $this->refundAmount = $refundAmount;

        return $this;
    }

    public function getRefundQuantity(): ?int
    {
        return $this->refundQuantity;
    }

    public function setRefundQuantity(int $refundQuantity): static
    {
        $this->refundQuantity = $refundQuantity;

        return $this;
    }
}
