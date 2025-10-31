<?php

namespace WechatPayBundle\Entity;

use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\PayOrderRepository;

/**
 * 微信支付单
 *
 * 因为微信的配置是可能会变化的，所以我们要实时记录真实提交时的信息，那就不跟其他实体强关联咯。
 * 对于接入方来说，这个实体是"渠道支付单"
 * TODO 微信支付单号在哪里获取的？
 *
 * @see https://www.cnblogs.com/goodAndyxublog/p/13882587.html
 */
#[ORM\Entity(repositoryClass: PayOrderRepository::class)]
#[ORM\Table(name: 'wechat_pay_order', options: ['comment' => '微信支付单'])]
class PayOrder implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;
    use IpTraceableAware;

    #[ORM\ManyToOne(targetEntity: PayOrder::class)]
    private ?PayOrder $parent = null;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AppID'])]
    #[Assert\NotBlank(message: 'AppID不能为空')]
    #[Assert\Length(max: 64, maxMessage: 'AppID长度不能超过 {{ limit }} 个字符')]
    private ?string $appId = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '商户ID'])]
    #[Assert\NotBlank(message: '商户ID不能为空')]
    #[Assert\Length(max: 64, maxMessage: '商户ID长度不能超过 {{ limit }} 个字符')]
    private ?string $mchId = null;

    #[ORM\Column(type: Types::STRING, length: 30, options: ['comment' => '交易类型'])]
    #[Assert\NotBlank(message: '交易类型不能为空')]
    #[Assert\Length(max: 30, maxMessage: '交易类型长度不能超过 {{ limit }} 个字符')]
    private ?string $tradeType = null;

    #[ORM\Column(type: Types::STRING, length: 80, unique: true, options: ['comment' => '商户订单号（按照微信的规则，下面这个单号在不同商户号之间是允许重复的，但为了减少逻辑，直接整体不可重复）'])]
    #[Assert\NotBlank(message: '商户订单号不能为空')]
    #[Assert\Length(max: 80, maxMessage: '商户订单号长度不能超过 {{ limit }} 个字符')]
    private ?string $tradeNo = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '商品描述'])]
    #[Assert\NotBlank(message: '商品描述不能为空')]
    #[Assert\Length(max: 255, maxMessage: '商品描述长度不能超过 {{ limit }} 个字符')]
    private ?string $body = null;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '标价币种'])]
    #[Assert\NotBlank(message: '标价币种不能为空')]
    #[Assert\Length(max: 10, maxMessage: '标价币种长度不能超过 {{ limit }} 个字符')]
    private string $feeType = 'CNY';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '标价金额'])]
    #[Assert\NotNull(message: '标价金额不能为空')]
    #[Assert\PositiveOrZero(message: '标价金额必须大于等于0')]
    private ?int $totalFee = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '交易起始时间'])]
    #[Assert\Type(type: \DateTimeInterface::class, message: '交易起始时间必须是有效的日期时间')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '交易结束时间'])]
    #[Assert\Type(type: \DateTimeInterface::class, message: '交易结束时间必须是有效的日期时间')]
    private ?\DateTimeInterface $expireTime = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '通知地址'])]
    #[Assert\NotBlank(message: '通知地址不能为空')]
    #[Assert\Url(message: '通知地址必须是有效的URL')]
    #[Assert\Length(max: 255, maxMessage: '通知地址长度不能超过 {{ limit }} 个字符')]
    private ?string $notifyUrl = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '用户标识'])]
    #[Assert\Length(max: 128, maxMessage: '用户标识长度不能超过 {{ limit }} 个字符')]
    private ?string $openId = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '附加数据'])]
    #[Assert\Length(max: 128, maxMessage: '附加数据长度不能超过 {{ limit }} 个字符')]
    private ?string $attach = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 100, maxMessage: '备注长度不能超过 {{ limit }} 个字符')]
    private ?string $remark = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求JSON'])]
    #[Assert\Json(message: '请求JSON必须是有效的JSON格式')]
    #[Assert\Length(max: 65535, maxMessage: '请求JSON内容过长')]
    private ?string $requestJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应JSON'])]
    #[Assert\Json(message: '响应JSON必须是有效的JSON格式')]
    #[Assert\Length(max: 65535, maxMessage: '响应JSON内容过长')]
    private ?string $responseJson = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '回调时间'])]
    #[Assert\Type(type: \DateTimeInterface::class, message: '回调时间必须是有效的日期时间')]
    private ?\DateTimeInterface $callbackTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '回调内容'])]
    #[Assert\Json(message: '回调内容必须是有效的JSON格式')]
    #[Assert\Length(max: 65535, maxMessage: '回调内容过长')]
    private ?string $callbackResponse = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PayOrderStatus::class, options: ['default' => 'init', 'comment' => '状态'])]
    #[Assert\Choice(callback: [PayOrderStatus::class, 'cases'], message: '无效的支付订单状态')]
    private PayOrderStatus $status;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '微信支付流水号'])]
    #[Assert\Length(max: 128, maxMessage: '微信支付流水号长度不能超过 {{ limit }} 个字符')]
    private ?string $transactionId = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '微信支付状态（SUCCESS：支付成功，REFUND：转入退款，NOTPAY：未支付，CLOSED：已关闭，REVOKED：已撤销，USERPAYING：用户支付中，PAYERROR：支付失败）'])]
    #[Assert\Length(max: 32, maxMessage: '微信支付状态长度不能超过 {{ limit }} 个字符')]
    private ?string $tradeState = null;

    /**
     * @var Collection<int, RefundOrder>
     */
    #[ORM\OneToMany(mappedBy: 'payOrder', targetEntity: RefundOrder::class)]
    private Collection $refundOrders;

    #[ORM\Column(length: 1000, nullable: true, options: ['comment' => '描述'])]
    #[Assert\Length(max: 1000, maxMessage: '描述长度不能超过 {{ limit }} 个字符')]
    private ?string $description = null;

    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '预支付交易会话标识（该值有效期为2小时，在PayOrderListener中调用远程接口生成）'])]
    #[Assert\Length(max: 64, maxMessage: '预支付交易会话标识长度不能超过 {{ limit }} 个字符')]
    private ?string $prepayId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '预支付交易会话过期时间'])]
    #[Assert\Type(type: \DateTimeInterface::class, message: '预支付交易会话过期时间必须是有效的日期时间')]
    private ?\DateTimeInterface $prepayExpireTime = null;

    public function __construct()
    {
        $this->refundOrders = new ArrayCollection();

        $startTime = CarbonImmutable::now();
        // 一般是15分钟后过期
        $expireTime = $startTime->clone()->addMinutes(15);
        $this->setStartTime($startTime);
        $this->setExpireTime($expireTime);
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getMchId(): ?string
    {
        return $this->mchId;
    }

    public function setMchId(string $mchId): void
    {
        $this->mchId = $mchId;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getTradeNo(): ?string
    {
        return $this->tradeNo;
    }

    public function setTradeNo(string $tradeNo): void
    {
        $this->tradeNo = $tradeNo;
    }

    public function getFeeType(): string
    {
        return $this->feeType;
    }

    public function setFeeType(string $feeType): void
    {
        $this->feeType = $feeType;
    }

    public function getTotalFee(): ?int
    {
        return $this->totalFee;
    }

    public function setTotalFee(int $totalFee): void
    {
        $this->totalFee = $totalFee;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getExpireTime(): ?\DateTimeInterface
    {
        return $this->expireTime;
    }

    public function setExpireTime(?\DateTimeInterface $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(string $notifyUrl): void
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function getTradeType(): ?string
    {
        return $this->tradeType;
    }

    public function setTradeType(string $tradeType): void
    {
        $this->tradeType = $tradeType;
    }

    public function getOpenId(): ?string
    {
        return $this->openId;
    }

    public function setOpenId(?string $openId): void
    {
        $this->openId = $openId;
    }

    public function getAttach(): ?string
    {
        return $this->attach;
    }

    public function setAttach(?string $attach): void
    {
        $this->attach = $attach;
    }

    public function getRequestJson(): ?string
    {
        return $this->requestJson;
    }

    public function setRequestJson(?string $requestJson): void
    {
        $this->requestJson = $requestJson;
    }

    public function getResponseJson(): ?string
    {
        return $this->responseJson;
    }

    public function setResponseJson(?string $responseJson): void
    {
        $this->responseJson = $responseJson;
    }

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): void
    {
        $this->merchant = $merchant;
    }

    public function getCallbackTime(): ?\DateTimeInterface
    {
        return $this->callbackTime;
    }

    public function setCallbackTime(?\DateTimeInterface $callbackTime): void
    {
        $this->callbackTime = $callbackTime;
    }

    public function getStatus(): PayOrderStatus
    {
        return $this->status;
    }

    public function setStatus(PayOrderStatus $status): void
    {
        $this->status = $status;
    }

    public function getCallbackResponse(): ?string
    {
        return $this->callbackResponse;
    }

    public function setCallbackResponse(?string $callbackResponse): void
    {
        $this->callbackResponse = $callbackResponse;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getTradeState(): ?string
    {
        return $this->tradeState;
    }

    public function setTradeState(?string $tradeState): void
    {
        $this->tradeState = $tradeState;
    }

    /**
     * @return Collection<int, RefundOrder>
     */
    public function getRefundOrders(): Collection
    {
        return $this->refundOrders;
    }

    public function addRefundOrder(RefundOrder $refundOrder): void
    {
        if (!$this->refundOrders->contains($refundOrder)) {
            $this->refundOrders->add($refundOrder);
            $refundOrder->setPayOrder($this);
        }
    }

    public function removeRefundOrder(RefundOrder $refundOrder): void
    {
        if ($this->refundOrders->removeElement($refundOrder)) {
            // set the owning side to null (unless already changed)
            if ($refundOrder->getPayOrder() === $this) {
                $refundOrder->setPayOrder(null);
            }
        }
    }

    public function getParent(): ?PayOrder
    {
        return $this->parent;
    }

    public function setParent(?PayOrder $parent): void
    {
        $this->parent = $parent;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPrepayId(): ?string
    {
        return $this->prepayId;
    }

    public function setPrepayId(?string $prepayId): void
    {
        $this->prepayId = $prepayId;
    }

    public function getPrepayExpireTime(): ?\DateTimeInterface
    {
        return $this->prepayExpireTime;
    }

    public function setPrepayExpireTime(?\DateTimeInterface $prepayExpireTime): void
    {
        $this->prepayExpireTime = $prepayExpireTime;
    }

    public function setResponseData(?string $responseData): void
    {
        $this->callbackResponse = $responseData;
    }

    public function setResponseSerial(?string $responseSerial): void
    {
        // TODO: 可能需要添加一个新字段来存储响应序列号
        // 暂时先存储在 remark 中或忽略
    }

    public function setSuccessTime(?string $successTime): void
    {
        if (null !== $successTime && '' !== $successTime) {
            $this->callbackTime = CarbonImmutable::parse($successTime);
        }
    }

    public function getCurrency(): string
    {
        return $this->getFeeType();
    }

    public function setCurrency(string $currency): void
    {
        $this->setFeeType($currency);
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
