<?php

namespace WechatPayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayBundle\Repository\RefundGoodsDetailRepository;

#[ORM\Entity(repositoryClass: RefundGoodsDetailRepository::class)]
#[ORM\Table(name: 'wechat_refund_goods_detail', options: ['comment' => '退款订单-商品明细'])]
class RefundGoodsDetail implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(inversedBy: 'goodsDetails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RefundOrder $refundOrder = null;

    #[ORM\Column(length: 32, options: ['comment' => '商户侧商品编码'])]
    #[Assert\NotBlank(message: '商户侧商品编码不能为空')]
    #[Assert\Length(max: 32, maxMessage: '商户侧商品编码长度不能超过 {{ limit }} 个字符')]
    private ?string $merchantGoodsId = null;

    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '微信支付商品编码'])]
    #[Assert\Length(max: 32, maxMessage: '微信支付商品编码长度不能超过 {{ limit }} 个字符')]
    private ?string $wechatpayGoodsId = null;

    #[ORM\Column(length: 256, nullable: true, options: ['comment' => '商品名称'])]
    #[Assert\Length(max: 256, maxMessage: '商品名称长度不能超过 {{ limit }} 个字符')]
    private ?string $goodsName = null;

    #[ORM\Column(options: ['comment' => '商品单价'])]
    #[Assert\NotNull(message: '商品单价不能为空')]
    #[Assert\PositiveOrZero(message: '商品单价必须大于等于0')]
    private ?int $unitPrice = null;

    #[ORM\Column(options: ['comment' => '商品退款金额'])]
    #[Assert\NotNull(message: '商品退款金额不能为空')]
    #[Assert\PositiveOrZero(message: '商品退款金额必须大于等于0')]
    private ?int $refundAmount = null;

    #[ORM\Column(options: ['comment' => '商品退货数量'])]
    #[Assert\NotNull(message: '商品退货数量不能为空')]
    #[Assert\PositiveOrZero(message: '商品退货数量必须大于等于0')]
    private ?int $refundQuantity = null;

    public function getRefundOrder(): ?RefundOrder
    {
        return $this->refundOrder;
    }

    public function setRefundOrder(?RefundOrder $refundOrder): void
    {
        $this->refundOrder = $refundOrder;
    }

    public function getMerchantGoodsId(): ?string
    {
        return $this->merchantGoodsId;
    }

    public function setMerchantGoodsId(string $merchantGoodsId): void
    {
        $this->merchantGoodsId = $merchantGoodsId;
    }

    public function getWechatpayGoodsId(): ?string
    {
        return $this->wechatpayGoodsId;
    }

    public function setWechatpayGoodsId(?string $wechatpayGoodsId): void
    {
        $this->wechatpayGoodsId = $wechatpayGoodsId;
    }

    public function getGoodsName(): ?string
    {
        return $this->goodsName;
    }

    public function setGoodsName(?string $goodsName): void
    {
        $this->goodsName = $goodsName;
    }

    public function getUnitPrice(): ?int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getRefundAmount(): ?int
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(int $refundAmount): void
    {
        $this->refundAmount = $refundAmount;
    }

    public function getRefundQuantity(): ?int
    {
        return $this->refundQuantity;
    }

    public function setRefundQuantity(int $refundQuantity): void
    {
        $this->refundQuantity = $refundQuantity;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
