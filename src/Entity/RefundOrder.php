<?php

namespace WechatPayBundle\Entity;

use AppBundle\Service\CurrencyManager;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineIpBundle\Attribute\UpdateIpColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\SelectField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
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
#[Deletable]
#[Editable]
#[Creatable]
#[ORM\Entity(repositoryClass: RefundOrderRepository::class)]
#[ORM\Table(name: 'wechat_refund_order', options: ['comment' => '退款订单'])]
class RefundOrder
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

    /**
     * 有支付了，才可能有退款单.
     */
    #[Ignore]
    #[ListColumn(title: '关联支付单')]
    #[ORM\ManyToOne(targetEntity: PayOrder::class, inversedBy: 'refundOrders')]
    private ?PayOrder $payOrder = null;

    #[ORM\Column(length: 50)]
    private ?string $appId = null;

    #[ORM\Column(type: Types::STRING, length: 80, nullable: true, options: ['comment' => '退款原因'])]
    private ?string $reason = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '退款结果回调url'])]
    private ?string $notifyUrl = null;

    #[SelectField(targetEntity: CurrencyManager::class)]
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '退款币种'])]
    private ?string $currency = 'CNY';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '退款金额'])]
    private ?int $money = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '请求JSON'])]
    private ?string $requestJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '响应JSON'])]
    private ?string $responseJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $callbackResponse = null;

    /**
     * 这个字段，是在微信退款接口成功之后才有的.
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '微信支付退款单号'])]
    private ?string $refundId = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '退款渠道'])]
    private ?string $refundChannel = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true, options: ['comment' => '退款入账账户'])]
    private ?string $userReceiveAccount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '退款成功时间'])]
    private ?\DateTimeInterface $successTime = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '退款状态'])]
    private ?string $status = null;

    #[ORM\OneToMany(mappedBy: 'refundOrder', targetEntity: RefundGoodsDetail::class)]
    private Collection $goodsDetails;

    #[CreateIpColumn]
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '创建者IP'])]
    private ?string $createdFromIp = null;

    #[UpdateIpColumn]
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '更新者IP'])]
    private ?string $updatedFromIp = null;

    public function __construct()
    {
        $this->goodsDetails = new ArrayCollection();
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

    public function getPayOrder(): ?PayOrder
    {
        return $this->payOrder;
    }

    public function setPayOrder(?PayOrder $payOrder): self
    {
        $this->payOrder = $payOrder;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(?string $notifyUrl): self
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMoney(): ?int
    {
        return $this->money;
    }

    public function setMoney(int $money): self
    {
        $this->money = $money;

        return $this;
    }

    public function getRequestJson(): ?string
    {
        return $this->requestJson;
    }

    public function setRequestJson(string $requestJson): self
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

    public function getRefundId(): ?string
    {
        return $this->refundId;
    }

    public function setRefundId(?string $refundId): self
    {
        $this->refundId = $refundId;

        return $this;
    }

    public function getRefundChannel(): ?string
    {
        return $this->refundChannel;
    }

    public function setRefundChannel(?string $refundChannel): self
    {
        $this->refundChannel = $refundChannel;

        return $this;
    }

    public function getUserReceiveAccount(): ?string
    {
        return $this->userReceiveAccount;
    }

    public function setUserReceiveAccount(?string $userReceiveAccount): self
    {
        $this->userReceiveAccount = $userReceiveAccount;

        return $this;
    }

    public function getSuccessTime(): ?\DateTimeInterface
    {
        return $this->successTime;
    }

    public function setSuccessTime(?\DateTimeInterface $successTime): self
    {
        $this->successTime = $successTime;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
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

    /**
     * 根据微信接口的返回结果，设置数据.
     *
     * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_10.shtml
     */
    public function processResponseData(array $response): void
    {
        if (!isset($response['refund_id'])) {
            return;
        }
        $this->setResponseJson(Json::encode($response));
        $this->setRefundId($response['refund_id']);
        $this->setRefundChannel($response['channel']);
        $this->setUserReceiveAccount($response['user_received_account']);
        $this->setSuccessTime(Carbon::parse($response['success_time']));
        $this->setCreateTime(Carbon::parse($response['create_time']));
        $this->setStatus($response['status']);
    }

    /**
     * @return Collection<int, RefundGoodsDetail>
     */
    public function getGoodsDetails(): Collection
    {
        return $this->goodsDetails;
    }

    public function addGoodsDetail(RefundGoodsDetail $goodsDetail): static
    {
        if (!$this->goodsDetails->contains($goodsDetail)) {
            $this->goodsDetails->add($goodsDetail);
            $goodsDetail->setRefundOrder($this);
        }

        return $this;
    }

    public function removeGoodsDetail(RefundGoodsDetail $goodsDetail): static
    {
        if ($this->goodsDetails->removeElement($goodsDetail)) {
            // set the owning side to null (unless already changed)
            if ($goodsDetail->getRefundOrder() === $this) {
                $goodsDetail->setRefundOrder(null);
            }
        }

        return $this;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): static
    {
        $this->appId = $appId;

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
}
