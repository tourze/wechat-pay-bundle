<?php

namespace WechatPayBundle\Entity;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineIpBundle\Attribute\UpdateIpColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\CurdAction;
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
    use TimestampableAware;

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

    #[ORM\ManyToOne(targetEntity: PayOrder::class)]
    private ?PayOrder $parent = null;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    private ?Merchant $merchant = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AppID'])]
    private ?string $appId = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '商户ID'])]
    private ?string $mchId = null;

    #[ORM\Column(type: Types::STRING, length: 30, options: ['comment' => '交易类型'])]
    private ?string $tradeType = null;

    /**
     * 按照微信的规则，下面这个单号在不同商户号之间是允许重复的，但为了减少逻辑，直接整体不可重复吧.
     */
    #[ORM\Column(type: Types::STRING, length: 80, unique: true, options: ['comment' => '商户订单号'])]
    private ?string $tradeNo = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '商品描述'])]
    private ?string $body = null;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '标价币种'])]
    private ?string $feeType = 'CNY';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '标价金额'])]
    private ?int $totalFee = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '终端IP'])]
    private ?string $createIp = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '交易起始时间'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '交易结束时间'])]
    private ?\DateTimeInterface $expireTime = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '通知地址'])]
    private ?string $notifyUrl = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '用户标识'])]
    private ?string $openId = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '附加数据'])]
    private ?string $attach = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requestJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $responseJson = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '回调时间'])]
    private ?\DateTimeInterface $callbackTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '回调内容'])]
    private ?string $callbackResponse = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PayOrderStatus::class, options: ['default' => 'init', 'comment' => '状态'])]
    private PayOrderStatus $status;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '微信支付流水号'])]
    private ?string $transactionId = null;

    /**
     * 交易状态，枚举值：
     * SUCCESS：支付成功
     * REFUND：转入退款
     * NOTPAY：未支付
     * CLOSED：已关闭
     * REVOKED：已撤销（付款码支付）
     * USERPAYING：用户支付中（付款码支付）
     * PAYERROR：支付失败(其他原因，如银行返回失败).
     */
    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '微信支付状态'])]
    private ?string $tradeState = null;

    /**
     * @var Collection<RefundOrder>
     */
    #[CurdAction(label: '退款记录')]
    #[ORM\OneToMany(mappedBy: 'payOrder', targetEntity: RefundOrder::class)]
    private Collection $refundOrders;

    #[ORM\Column(length: 1000, nullable: true, options: ['comment' => '描述'])]
    private ?string $description = null;

    /**
     * 该值有效期为2小时
     * 这个值会在 PayOrderListener 中调用远程接口生成
     */
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '预支付交易会话标识'])]
    private ?string $prepayId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '预支付交易会话过期时间'])]
    private ?\DateTimeInterface $prepayExpireTime = null;

    #[CreateIpColumn]
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '创建者IP'])]
    private ?string $createdFromIp = null;

    #[UpdateIpColumn]
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '更新者IP'])]
    private ?string $updatedFromIp = null;

    public function __construct()
    {
        $this->refundOrders = new ArrayCollection();

        $startTime = Carbon::now();
        // 一般是15分钟后过期
        $expireTime = $startTime->clone()->addMinutes(15);
        $this->setStartTime($startTime);
        $this->setExpireTime($expireTime);
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

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;

        return $this;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): self
    {
        $this->appId = $appId;

        return $this;
    }

    public function getMchId(): ?string
    {
        return $this->mchId;
    }

    public function setMchId(string $mchId): self
    {
        $this->mchId = $mchId;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getTradeNo(): ?string
    {
        return $this->tradeNo;
    }

    public function setTradeNo(string $tradeNo): self
    {
        $this->tradeNo = $tradeNo;

        return $this;
    }

    public function getFeeType(): ?string
    {
        return $this->feeType;
    }

    public function setFeeType(string $feeType): self
    {
        $this->feeType = $feeType;

        return $this;
    }

    public function getTotalFee(): ?int
    {
        return $this->totalFee;
    }

    public function setTotalFee(int $totalFee): self
    {
        $this->totalFee = $totalFee;

        return $this;
    }

    public function getCreateIp(): ?string
    {
        return $this->createIp;
    }

    public function setCreateIp(string $createIp): self
    {
        $this->createIp = $createIp;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getExpireTime(): ?\DateTimeInterface
    {
        return $this->expireTime;
    }

    public function setExpireTime(?\DateTimeInterface $expireTime): self
    {
        $this->expireTime = $expireTime;

        return $this;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(string $notifyUrl): self
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

    public function getTradeType(): ?string
    {
        return $this->tradeType;
    }

    public function setTradeType(string $tradeType): self
    {
        $this->tradeType = $tradeType;

        return $this;
    }

    public function getOpenId(): ?string
    {
        return $this->openId;
    }

    public function setOpenId(?string $openId): self
    {
        $this->openId = $openId;

        return $this;
    }

    public function getAttach(): ?string
    {
        return $this->attach;
    }

    public function setAttach(?string $attach): self
    {
        $this->attach = $attach;

        return $this;
    }

    public function getRequestJson(): ?string
    {
        return $this->requestJson;
    }

    public function setRequestJson(?string $requestJson): self
    {
        $this->requestJson = $requestJson;

        return $this;
    }

    public function getResponseJson(): ?string
    {
        return $this->responseJson;
    }

    public function setResponseJson(?string $responseJson): self
    {
        $this->responseJson = $responseJson;

        return $this;
    }

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): self
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function getCallbackTime(): ?\DateTimeInterface
    {
        return $this->callbackTime;
    }

    public function setCallbackTime(?\DateTimeInterface $callbackTime): self
    {
        $this->callbackTime = $callbackTime;

        return $this;
    }

    public function getStatus(): PayOrderStatus
    {
        return $this->status;
    }

    public function setStatus(PayOrderStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCallbackResponse(): ?string
    {
        return $this->callbackResponse;
    }

    public function setCallbackResponse(?string $callbackResponse): self
    {
        $this->callbackResponse = $callbackResponse;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getTradeState(): ?string
    {
        return $this->tradeState;
    }

    public function setTradeState(?string $tradeState): self
    {
        $this->tradeState = $tradeState;

        return $this;
    }

    /**
     * @return Collection<int, RefundOrder>
     */
    public function getRefundOrders(): Collection
    {
        return $this->refundOrders;
    }

    public function addRefundOrder(RefundOrder $refundOrder): self
    {
        if (!$this->refundOrders->contains($refundOrder)) {
            $this->refundOrders[] = $refundOrder;
            $refundOrder->setPayOrder($this);
        }

        return $this;
    }

    public function removeRefundOrder(RefundOrder $refundOrder): self
    {
        if ($this->refundOrders->removeElement($refundOrder)) {
            // set the owning side to null (unless already changed)
            if ($refundOrder->getPayOrder() === $this) {
                $refundOrder->setPayOrder(null);
            }
        }

        return $this;
    }

    public function getParent(): ?PayOrder
    {
        return $this->parent;
    }

    public function setParent(?PayOrder $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrepayId(): ?string
    {
        return $this->prepayId;
    }

    public function setPrepayId(?string $prepayId): static
    {
        $this->prepayId = $prepayId;

        return $this;
    }

    public function getPrepayExpireTime(): ?\DateTimeInterface
    {
        return $this->prepayExpireTime;
    }

    public function setPrepayExpireTime(?\DateTimeInterface $prepayExpireTime): static
    {
        $this->prepayExpireTime = $prepayExpireTime;

        return $this;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function setCreatedFromIp(?string $createdFromIp): self
    {
        $this->createdFromIp = $createdFromIp;

        return $this;
    }

    public function getUpdatedFromIp(): ?string
    {
        return $this->updatedFromIp;
    }

    public function setUpdatedFromIp(?string $updatedFromIp): self
    {
        $this->updatedFromIp = $updatedFromIp;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
