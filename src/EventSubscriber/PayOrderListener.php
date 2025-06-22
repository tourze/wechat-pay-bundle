<?php

namespace WechatPayBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[AsEntityListener(event: Events::prePersist, method: 'saveCallbackUrl', entity: PayOrder::class)]
#[AsEntityListener(event: Events::postRemove, method: 'closeOrder', entity: PayOrder::class)]
class PayOrderListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WechatPayBuilder $payBuilder,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function saveCallbackUrl(PayOrder $order): void
    {
        // 回调地址如果没设置，就自动生成
        if ($order->getNotifyUrl() !== null) {
            return;
        }
        $order->setNotifyUrl($this->urlGenerator->generate('wechat_mini_program_pay_callback', [
            'appId' => $order->getAppId(),
            'traderNo' => $order->getTradeNo(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
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
