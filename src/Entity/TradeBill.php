<?php

namespace WechatPayBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use WechatPayBundle\Enum\BillType;
use WechatPayBundle\Repository\TradeBillRepository;

/**
 * @see https://pay.weixin.qq.com/docs/merchant/products/bill-download/format-trade.html 内容格式参考
 */
#[ORM\Entity(repositoryClass: TradeBillRepository::class)]
#[ORM\Table(name: 'ims_wechat_payment_trade_bill', options: ['comment' => '微信支付-交易账单'])]
class TradeBill implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    public function getId(): ?int
    {
        return $this->id;
    }
    use TimestampableAware;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => '账单日期'])]
    private ?\DateTimeInterface $billDate = null;

    #[ORM\Column(length: 20, enumType: BillType::class, options: ['comment' => '账单类型'])]
    private BillType $billType = BillType::ALL;

    #[ORM\Column(length: 20, options: ['comment' => '哈希类型'])]
    private ?string $hashType = null;

    #[ORM\Column(length: 1024, nullable: true, options: ['comment' => '哈希值'])]
    private ?string $hashValue = null;

    #[ORM\Column(length: 2048, options: ['comment' => '下载地址'])]
    private ?string $downloadUrl = null;

    #[ORM\Column(length: 255, options: ['comment' => '本地路径'])]
    private ?string $localFile = null;

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): static
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function getBillDate(): ?\DateTimeInterface
    {
        return $this->billDate;
    }

    public function setBillDate(\DateTimeInterface $billDate): static
    {
        $this->billDate = $billDate;

        return $this;
    }

    public function getBillType(): BillType
    {
        return $this->billType;
    }

    public function setBillType(BillType $billType): static
    {
        $this->billType = $billType;

        return $this;
    }

    public function getHashType(): ?string
    {
        return $this->hashType;
    }

    public function setHashType(string $hashType): static
    {
        $this->hashType = $hashType;

        return $this;
    }

    public function getHashValue(): ?string
    {
        return $this->hashValue;
    }

    public function setHashValue(?string $hashValue): static
    {
        $this->hashValue = $hashValue;

        return $this;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(string $downloadUrl): static
    {
        $this->downloadUrl = $downloadUrl;

        return $this;
    }

    public function getLocalFile(): ?string
    {
        return $this->localFile;
    }

    public function setLocalFile(string $localFile): static
    {
        $this->localFile = $localFile;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
