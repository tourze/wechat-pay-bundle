<?php

namespace WechatPayBundle\Entity;

use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayBundle\Repository\RefundOrderRepository;
use Yiisoft\Json\Json;

/**
 * 退款订单
 *
 * 当交易发生之后一年内，由于买家或者卖家的原因需要退款时，卖家可以通过退款接口将支付金额退还给买家，微信支付将在收到退款请求并且验证成功之后，将支付款按原路退还至买家账号上。
 * 根据微信说明，退款是要跟支付单关联的，并且不能超出支付单的总金额。
 *
 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_5_9.shtml
 * @see https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_4
 */
#[ORM\Entity(repositoryClass: RefundOrderRepository::class)]
#[ORM\Table(name: 'wechat_refund_order', options: ['comment' => '退款订单'])]
class RefundOrder implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;
    use IpTraceableAware;

    /**
     * 有支付了，才可能有退款单.
     */
    #[Ignore]
    #[ORM\ManyToOne(targetEntity: PayOrder::class, inversedBy: 'refundOrders')]
    private ?PayOrder $payOrder = null;

    #[ORM\Column(length: 50, options: ['comment' => '应用ID'])]
    #[Assert\NotBlank(message: '应用ID不能为空')]
    #[Assert\Length(max: 50, maxMessage: '应用ID长度不能超过 {{ limit }} 个字符')]
    private ?string $appId = null;

    #[ORM\Column(type: Types::STRING, length: 80, nullable: true, options: ['comment' => '退款原因'])]
    #[Assert\Length(max: 80, maxMessage: '退款原因长度不能超过 {{ limit }} 个字符')]
    private ?string $reason = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '退款结果回调url'])]
    #[Assert\Url(message: '退款结果回调url必须是有效的URL')]
    #[Assert\Length(max: 255, maxMessage: '退款结果回调url长度不能超过 {{ limit }} 个字符')]
    private ?string $notifyUrl = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '退款币种'])]
    #[Assert\Length(max: 10, maxMessage: '退款币种长度不能超过 {{ limit }} 个字符')]
    private ?string $currency = 'CNY';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '退款金额'])]
    #[Assert\NotNull(message: '退款金额不能为空')]
    #[Assert\PositiveOrZero(message: '退款金额必须大于等于0')]
    private ?int $money = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求JSON'])]
    #[Assert\Json(message: '请求JSON必须是有效的JSON格式')]
    #[Assert\Length(max: 65535, maxMessage: '请求JSON内容过长')]
    private ?string $requestJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应JSON'])]
    #[Assert\Json(message: '响应JSON必须是有效的JSON格式')]
    #[Assert\Length(max: 65535, maxMessage: '响应JSON内容过长')]
    private ?string $responseJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '回调响应'])]
    #[Assert\Json(message: '回调响应必须是有效的JSON格式')]
    #[Assert\Length(max: 65535, maxMessage: '回调响应内容过长')]
    private ?string $callbackResponse = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '微信支付退款单号'])]
    #[Assert\Length(max: 100, maxMessage: '微信支付退款单号长度不能超过 {{ limit }} 个字符')]
    private ?string $refundId = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '退款渠道'])]
    #[Assert\Length(max: 32, maxMessage: '退款渠道长度不能超过 {{ limit }} 个字符')]
    private ?string $refundChannel = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true, options: ['comment' => '退款入账账户'])]
    #[Assert\Length(max: 120, maxMessage: '退款入账账户长度不能超过 {{ limit }} 个字符')]
    private ?string $userReceiveAccount = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '退款成功时间'])]
    #[Assert\Type(type: \DateTimeInterface::class, message: '退款成功时间必须是有效的日期时间')]
    private ?\DateTimeInterface $successTime = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '退款状态'])]
    #[Assert\Length(max: 64, maxMessage: '退款状态长度不能超过 {{ limit }} 个字符')]
    private ?string $status = null;

    /**
     * @var Collection<int, RefundGoodsDetail>
     */
    #[ORM\OneToMany(mappedBy: 'refundOrder', targetEntity: RefundGoodsDetail::class)]
    private Collection $goodsDetails;

    public function __construct()
    {
        $this->goodsDetails = new ArrayCollection();
    }

    public function getPayOrder(): ?PayOrder
    {
        return $this->payOrder;
    }

    public function setPayOrder(?PayOrder $payOrder): void
    {
        $this->payOrder = $payOrder;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(?string $notifyUrl): void
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getMoney(): ?int
    {
        return $this->money;
    }

    public function setMoney(int $money): void
    {
        $this->money = $money;
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

    public function getRefundId(): ?string
    {
        return $this->refundId;
    }

    public function setRefundId(?string $refundId): void
    {
        $this->refundId = $refundId;
    }

    public function getRefundChannel(): ?string
    {
        return $this->refundChannel;
    }

    public function setRefundChannel(?string $refundChannel): void
    {
        $this->refundChannel = $refundChannel;
    }

    public function getUserReceiveAccount(): ?string
    {
        return $this->userReceiveAccount;
    }

    public function setUserReceiveAccount(?string $userReceiveAccount): void
    {
        $this->userReceiveAccount = $userReceiveAccount;
    }

    public function getSuccessTime(): ?\DateTimeInterface
    {
        return $this->successTime;
    }

    public function setSuccessTime(?\DateTimeInterface $successTime): void
    {
        $this->successTime = $successTime;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
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

    /**
     * 根据微信接口的返回结果，设置数据.
     *
     * @param array<string, mixed> $response
     * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_10.shtml
     */
    public function processResponseData(array $response): void
    {
        if (!isset($response['refund_id'])) {
            return;
        }
        $this->setResponseJson(Json::encode($response));
        $this->processBasicFields($response);
        $this->processTimeFields($response);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function processBasicFields(array $response): void
    {
        $refundId = \is_string($response['refund_id']) ? $response['refund_id'] : null;
        $this->setRefundId($refundId);

        $channel = isset($response['channel']) && \is_string($response['channel']) ? $response['channel'] : null;
        $this->setRefundChannel($channel);

        $userReceiveAccount = isset($response['user_received_account']) && \is_string($response['user_received_account']) ? $response['user_received_account'] : null;
        $this->setUserReceiveAccount($userReceiveAccount);

        $status = isset($response['status']) && \is_string($response['status']) ? $response['status'] : null;
        $this->setStatus($status);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function processTimeFields(array $response): void
    {
        if (isset($response['success_time'])) {
            $successTime = $response['success_time'];
            if (\is_string($successTime) || \is_int($successTime)) {
                $this->setSuccessTime(CarbonImmutable::parse($successTime));
            }
        }

        if (isset($response['create_time'])) {
            $createTime = $response['create_time'];
            if (\is_string($createTime) || \is_int($createTime)) {
                $this->setCreateTime(CarbonImmutable::parse($createTime));
            }
        }
    }

    /**
     * @return Collection<int, RefundGoodsDetail>
     */
    public function getGoodsDetails(): Collection
    {
        return $this->goodsDetails;
    }

    public function addGoodsDetail(RefundGoodsDetail $goodsDetail): void
    {
        if (!$this->goodsDetails->contains($goodsDetail)) {
            $this->goodsDetails->add($goodsDetail);
            $goodsDetail->setRefundOrder($this);
        }
    }

    public function removeGoodsDetail(RefundGoodsDetail $goodsDetail): void
    {
        if ($this->goodsDetails->removeElement($goodsDetail)) {
            // set the owning side to null (unless already changed)
            if ($goodsDetail->getRefundOrder() === $this) {
                $goodsDetail->setRefundOrder(null);
            }
        }
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
