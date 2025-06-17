<?php

namespace WechatPayBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Filter\Keyword;
use WechatPayBundle\Repository\MerchantRepository;

/**
 * 微信支付-商户号
 *
 * 为了简化模型，我们只使用V3的接口
 */
#[Deletable]
#[Editable]
#[Creatable]
#[ORM\Entity(repositoryClass: MerchantRepository::class)]
#[ORM\Table(name: 'wechat_payment_merchant', options: ['comment' => '微信支付商户配置'])]
class Merchant implements \Stringable
{
    use TimestampableAware;

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

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private ?bool $valid = false;

    #[FormField(span: 10)]
    #[Filterable]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 64, unique: true, options: ['comment' => '商户号'])]
    private string $mchId;

    #[FormField(span: 14)]
    #[Filterable]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '秘钥key'])]
    private ?string $apiKey = null;

    #[FormField]
    #[Keyword]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '商户API私钥'])]
    private ?string $pemKey = null;

    #[FormField]
    #[Keyword]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '证书序列号'])]
    private ?string $certSerial = null;

    #[FormField]
    #[Keyword]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '微信支付平台证书'])]
    private ?string $pemCert = null;

    #[Keyword]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getMchId();
    }

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

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getMchId(): string
    {
        return $this->mchId;
    }

    public function setMchId(string $mchId): self
    {
        $this->mchId = $mchId;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getPemCert(): ?string
    {
        return $this->pemCert;
    }

    public function setPemCert(?string $pemCert): self
    {
        $this->pemCert = $pemCert;

        return $this;
    }

    public function getPemKey(): ?string
    {
        return $this->pemKey;
    }

    public function setPemKey(?string $pemKey): self
    {
        $this->pemKey = $pemKey;

        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;

        return $this;
    }

    public function getCertSerial(): ?string
    {
        return $this->certSerial;
    }

    public function setCertSerial(string $certSerial): self
    {
        $this->certSerial = $certSerial;

        return $this;
    }
}
