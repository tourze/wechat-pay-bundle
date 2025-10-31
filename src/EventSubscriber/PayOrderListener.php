<?php

namespace WechatPayBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[AsEntityListener(event: Events::prePersist, method: 'saveCallbackUrl', entity: PayOrder::class)]
#[AsEntityListener(event: Events::postRemove, method: 'closeOrder', entity: PayOrder::class)]
#[WithMonologChannel(channel: 'wechat_pay')]
readonly class PayOrderListener
{
    public function __construct(
        private LoggerInterface $logger,
        private WechatPayBuilder $payBuilder,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function saveCallbackUrl(PayOrder $order): void
    {
        // 回调地址如果没设置，就自动生成
        if (null !== $order->getNotifyUrl()) {
            return;
        }

        try {
            $order->setNotifyUrl($this->urlGenerator->generate('wechat_mini_program_pay_callback', [
                'appId' => $order->getAppId(),
                'traderNo' => $order->getTradeNo(),
            ], UrlGeneratorInterface::ABSOLUTE_URL));
        } catch (RouteNotFoundException $e) {
            // 如果路由不存在，记录日志但不抛出异常
            $this->logger->warning('微信支付回调路由不存在，请确保 wechat-mini-program-pay-bundle 已正确安装和配置', [
                'route' => 'wechat_mini_program_pay_callback',
                'order_id' => $order->getId(),
                'trade_no' => $order->getTradeNo(),
            ]);
        }
    }

    /**
     * 本地删除订单的话，我们远程也关闭订单
     */
    public function closeOrder(PayOrder $order): void
    {
        try {
            $merchant = $order->getMerchant();
            if (null === $merchant) {
                $this->logger->warning('订单关闭失败：商户信息不存在', ['order_id' => $order->getId()]);

                return;
            }
            $builder = $this->payBuilder->genBuilder($merchant);
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
