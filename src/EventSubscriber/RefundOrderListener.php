<?php

namespace WechatPayBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WechatPayBundle\Entity\RefundOrder;
use WechatPayBundle\Repository\RefundOrderRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[AsEntityListener(event: Events::prePersist, method: 'ensureCallbackURL', entity: RefundOrder::class)]
#[AsEntityListener(event: Events::postPersist, method: 'callRemote', entity: RefundOrder::class)]
class RefundOrderListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WechatPayBuilder $payBuilder,
        private readonly RefundOrderRepository $refundOrderRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 保存时，确保有写入一个回调地址
     */
    public function ensureCallbackURL(RefundOrder $refundOrder): void
    {
        if ($refundOrder->getNotifyUrl()) {
            return;
        }

        $refundOrder->setNotifyUrl($this->urlGenerator->generate('wechat_mini_program_refund_callback', [
            'appId' => $refundOrder->getAppId(),
            'orderId' => $refundOrder->getPayOrder()->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function callRemote(RefundOrder $refundOrder): void
    {
        try {
            // TODO goods_detail 现在都没处理

            $requestJson = [
                'out_trade_no' => $refundOrder->getPayOrder()->getTradeNo(),
                'out_refund_no' => $refundOrder->getId(),
                'reason' => $refundOrder->getReason(),
                'notify_url' => $refundOrder->getNotifyUrl(),
                'amount' => [
                    // 【退款金额】 退款金额，单位为分，只能为整数，不能超过原订单支付金额。
                    'refund' => $refundOrder->getMoney(),
                    // 【原订单金额】 原支付交易的订单总金额，单位为分，只能为整数。
                    'total' => $refundOrder->getPayOrder()->getTotalFee(),
                    'currency' => $refundOrder->getCurrency(),
                ],
            ];
            $refundOrder->setRequestJson(Json::encode($requestJson));

            $builder = $this->payBuilder->genBuilder($refundOrder->getPayOrder()->getMerchant());
            $response = $builder->chain('/v3/refund/domestic/refunds')->post([
                'json' => $requestJson,
            ]);
            $response = $response->getBody()->getContents();
            $refundOrder->setResponseJson($response);
            $response = Json::decode($response);
            $this->logger->info('微信退款接口结果', [
                'request' => $requestJson,
                'response' => $response,
            ]);
            $refundOrder->processResponseData($response);
            $this->entityManager->persist($refundOrder);
            $this->entityManager->flush();

            // 还需要执行 wechat:refund:check-order-status 命令来定期检查退款状态
        } catch (\Throwable $exception) {
            $this->logger->error('远程退款发生错误', [
                'refundOrder' => $refundOrder,
                'exception' => $exception,
            ]);
        }
    }
}
