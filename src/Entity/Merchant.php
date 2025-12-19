<?php

namespace WechatPayBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayBundle\Repository\MerchantRepository;

/**
 * 微信支付-商户号
 *
 * 为了简化模型，我们只使用V3的接口
 */
#[ORM\Entity(repositoryClass: MerchantRepository::class)]
#[ORM\Table(name: 'wechat_payment_merchant', options: ['comment' => '微信支付商户配置'])]
class Merchant implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[Assert\Type(type: 'bool', message: '有效状态必须是布尔值')]
    private ?bool $valid = false;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true, options: ['comment' => '商户号'])]
    #[Assert\NotBlank(message: '商户号不能为空')]
    #[Assert\Length(max: 64, maxMessage: '商户号长度不能超过 {{ limit }} 个字符')]
    private string $mchId;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '秘钥key'])]
    #[Assert\Length(max: 100, maxMessage: 'API秘钥长度不能超过 {{ limit }} 个字符')]
    private ?string $apiKey = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '秘钥key v3版本，如果有优先读取这个，否则默认度取apiKey'])]
    #[Assert\Length(max: 100, maxMessage: 'API秘钥长度不能超过 {{ limit }} 个字符')]
    private ?string $apiKeyV3 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '商户API私钥'])]
    #[Assert\Length(max: 10000, maxMessage: '商户API私钥内容过长')]
    private ?string $pemKey = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '证书序列号'])]
    #[Assert\Length(max: 128, maxMessage: '证书序列号长度不能超过 {{ limit }} 个字符')]
    private ?string $certSerial = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '微信支付平台证书'])]
    #[Assert\Length(max: 10000, maxMessage: '微信支付平台证书内容过长')]
    private ?string $pemCert = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '微信支付公钥'])]
    #[Assert\Length(max: 10000, maxMessage: '微信')]
    private ?string $publicKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '微信支付公钥ID'])]
    #[Assert\Length(max: 200, maxMessage: '微信')]
    private ?string $publicKeyId = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 100, maxMessage: '备注长度不能超过 {{ limit }} 个字符')]
    private ?string $remark = null;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return $this->getMchId();
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getMchId(): string
    {
        return $this->mchId;
    }

    public function setMchId(string $mchId): void
    {
        $this->mchId = $mchId;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getPemCert(): ?string
    {
        return $this->pemCert;
    }

    public function setPemCert(?string $pemCert): void
    {
        $this->pemCert = $pemCert;
    }

    public function getPemKey(): ?string
    {
        return $this->pemKey;
    }

    public function setPemKey(?string $pemKey): void
    {
        $this->pemKey = $pemKey;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getCertSerial(): ?string
    {
        return $this->certSerial;
    }

    public function setCertSerial(?string $certSerial): void
    {
        $this->certSerial = $certSerial;
    }

    public function getKey(): ?string
    {
        return $this->getApiKey();
    }

    /**
     * @return string|null
     */
    public function getApiKeyV3(): ?string
    {
        return $this->apiKeyV3;
    }

    /**
     * @param string|null $apiKeyV3
     */
    public function setApiKeyV3(?string $apiKeyV3): void
    {
        $this->apiKeyV3 = $apiKeyV3;
    }

    /**
     * @return string|null
     */
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    /**
     * @param string|null $publicKey
     */
    public function setPublicKey(?string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string|null
     */
    public function getPublicKeyId(): ?string
    {
        return $this->publicKeyId;
    }

    /**
     * @param string|null $publicKeyId
     */
    public function setPublicKeyId(?string $publicKeyId): void
    {
        $this->publicKeyId = $publicKeyId;
    }
}
