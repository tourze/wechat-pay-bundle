<?php

namespace WechatPayBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayBundle\Repository\RefundGoodsDetailRepository;

#[ORM\Entity(repositoryClass: RefundGoodsDetailRepository::class)]
#[ORM\Table(name: 'wechat_refund_goods_detail', options: ['comment' => '退款订单-商品明细'])]
class RefundGoodsDetail implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

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

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
