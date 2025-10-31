<?php

namespace WechatPayBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
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
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '账单日期'])]
    #[Assert\NotNull(message: '账单日期不能为空')]
    private ?\DateTimeInterface $billDate = null;

    #[ORM\Column(length: 20, enumType: BillType::class, options: ['comment' => '账单类型'])]
    #[Assert\Choice(callback: [BillType::class, 'cases'], message: '无效的账单类型')]
    private BillType $billType = BillType::ALL;

    #[ORM\Column(length: 20, options: ['comment' => '哈希类型'])]
    #[Assert\NotBlank(message: '哈希类型不能为空')]
    #[Assert\Length(max: 20, maxMessage: '哈希类型长度不能超过 {{ limit }} 个字符')]
    private ?string $hashType = null;

    #[ORM\Column(length: 1024, nullable: true, options: ['comment' => '哈希值'])]
    #[Assert\Length(max: 1024, maxMessage: '哈希值长度不能超过 {{ limit }} 个字符')]
    private ?string $hashValue = null;

    #[ORM\Column(length: 2048, options: ['comment' => '下载地址'])]
    #[Assert\NotBlank(message: '下载地址不能为空')]
    #[Assert\Url(message: '下载地址必须是有效的URL')]
    #[Assert\Length(max: 2048, maxMessage: '下载地址长度不能超过 {{ limit }} 个字符')]
    private ?string $downloadUrl = null;

    #[ORM\Column(length: 255, options: ['comment' => '本地路径'])]
    #[Assert\NotBlank(message: '本地路径不能为空')]
    #[Assert\Length(max: 255, maxMessage: '本地路径长度不能超过 {{ limit }} 个字符')]
    private ?string $localFile = null;

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): void
    {
        $this->merchant = $merchant;
    }

    public function getBillDate(): ?\DateTimeInterface
    {
        return $this->billDate;
    }

    public function setBillDate(\DateTimeInterface $billDate): void
    {
        $this->billDate = $billDate;
    }

    public function getBillType(): BillType
    {
        return $this->billType;
    }

    public function setBillType(BillType $billType): void
    {
        $this->billType = $billType;
    }

    public function getHashType(): ?string
    {
        return $this->hashType;
    }

    public function setHashType(string $hashType): void
    {
        $this->hashType = $hashType;
    }

    public function getHashValue(): ?string
    {
        return $this->hashValue;
    }

    public function setHashValue(?string $hashValue): void
    {
        $this->hashValue = $hashValue;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(string $downloadUrl): void
    {
        $this->downloadUrl = $downloadUrl;
    }

    public function getLocalFile(): ?string
    {
        return $this->localFile;
    }

    public function setLocalFile(string $localFile): void
    {
        $this->localFile = $localFile;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
