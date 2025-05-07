<?php

namespace WechatPayBundle\EventSubscriber;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[AsEntityListener(event: Events::prePersist, method: 'saveCallbackUrl', entity: PayOrder::class)]
#[AsEntityListener(event: Events::prePersist, method: 'savePrepayId', entity: PayOrder::class)]
#[AsEntityListener(event: Events::postRemove, method: 'closeOrder', entity: PayOrder::class)]
class PayOrderListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WechatPayBuilder $payBuilder,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MerchantRepository $merchantRepository,
    ) {
    }

    public function saveCallbackUrl(PayOrder $order): void
    {
        // 回调地址如果没设置，就自动生成
        if ($order->getNotifyUrl()) {
            return;
        }
        $order->setNotifyUrl($this->urlGenerator->generate('wechat_mini_program_pay_callback', [
            'appId' => $order->getAppId(),
            'traderNo' => $order->getTradeNo(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function savePrepayId(PayOrder $order): void
    {
        return;
        if ($order->getPrepayId()) {
            return;
        }

        $merchant = $this->merchantRepository->findOneBy(['mchId' => $order->getMchId()]);
        if (!$merchant) {
            return;
        }

        // 调用远程接口统一下单
        $requestJson = [
            'appid' => $order->getAppId(),
            'mchid' => $order->getMchId(),
            'description' => $order->getDescription(),
            'out_trade_no' => $order->getTradeNo(),
            'time_expire' => $order->getExpireTime()->format('Y-m-dTH:i:s+08:00'),
            'attach' => $order->getAttach(),
            'notify_url' => $order->getNotifyUrl(),
            'amount' => [
                'total' => $order->getTotalFee(),
                'currency' => $order->getFeeType(),
            ],
            'payer' => [
                'openid' => $order->getOpenId(),
            ],
        ];
        $order->setRequestJson(Json::encode($requestJson));
        $builder = $this->payBuilder->genBuilder($merchant);
        $response = $builder->chain('v3/pay/transactions/jsapi')->post([
            'json' => $requestJson,
        ]);
        $response = $response->getBody()->getContents();
        $order->setResponseJson($response);
        $response = Json::decode($response);
        $this->logger->info('微信下单接口', [
            'request' => $requestJson,
            'response' => $response,
        ]);

        // 保存起来，预支付交易会话标识。用于后续接口调用中使用，该值有效期为2小时
        if (isset($response['prepay_id'])) {
            $order->setPrepayId($response['prepay_id']);
            $order->setPrepayExpireTime(Carbon::now()->addHours(2));
        }
    }

    /**
     * 本地删除订单的话，我们远程也关闭订单
     */
    public function closeOrder(PayOrder $order): void
    {
        try {
            $builder = $this->payBuilder->genBuilder($order->getMerchant());
            $response = $builder->chain("/v3/pay/transactions/out-trade-no/{$order->getTradeNo()}/close")->post([
                'json' => Json::encode([
                    'mchid' => $order->getMchId(),
                ]),
            ]);
            $response = $response->getBody()->getContents();
            $this->logger->info('主动关闭微信支付单结果', [
                'response' => $response,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('主动关闭微信支付单失败', [
                'exception' => $exception,
                'order' => $order,
            ]);
        }
    }
}
