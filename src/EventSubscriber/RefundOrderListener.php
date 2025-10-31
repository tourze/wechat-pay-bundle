<?php

namespace WechatPayBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WechatPayBundle\Entity\RefundOrder;

#[AsEntityListener(event: Events::postPersist, method: 'ensureCallbackURL', entity: RefundOrder::class)]
#[WithMonologChannel(channel: 'wechat_pay')]
readonly class RefundOrderListener
{
    public function __construct(
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 保存时，确保有写入一个回调地址
     */
    public function ensureCallbackURL(RefundOrder $refundOrder): void
    {
        $notifyUrl = $refundOrder->getNotifyUrl();
        if (null !== $notifyUrl && '' !== $notifyUrl) {
            return;
        }

        $payOrder = $refundOrder->getPayOrder();
        if (null === $payOrder) {
            $this->logger->error('RefundOrder 没有关联的 PayOrder', [
                'refundOrderId' => $refundOrder->getId(),
            ]);

            return;
        }

        try {
            $refundOrder->setNotifyUrl($this->urlGenerator->generate('wechat_mini_program_refund_callback', [
                'appId' => $refundOrder->getAppId(),
                'refundNo' => $refundOrder->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL));

            // 在postPersist后更新实体
            $this->entityManager->persist($refundOrder);
            $this->entityManager->flush();
        } catch (RouteNotFoundException $e) {
            // 如果路由不存在，记录日志但不抛出异常
            $this->logger->warning('微信退款回调路由不存在，请确保 wechat-mini-program-pay-bundle 已正确安装和配置', [
                'route' => 'wechat_mini_program_refund_callback',
                'refund_order_id' => $refundOrder->getId(),
                'app_id' => $refundOrder->getAppId(),
            ]);
        }
    }
}
