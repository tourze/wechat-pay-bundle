<?php

namespace WechatPayBundle\Command;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use WeChatPay\BuilderChainable;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Exception\PaymentParameterException;
use WechatPayBundle\Repository\PayOrderRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * 根据微信的文档说明，对于那些久久没有回调的订单，我们需要定时任务去检查一下他们的状态
 *
 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_5_5.shtml
 */
#[AsCronTask(expression: '* * * * *')]
#[AsCommand(name: self::NAME, description: '检查订单过期状态')]
#[WithMonologChannel(channel: 'wechat_pay')]
class PayCheckOrderExpireCommand extends Command
{
    public const NAME = 'wechat:pay:check-order-expire';

    public function __construct(
        private readonly PayOrderRepository $payOrderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WechatPayBuilder $wechatPayBuilder,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $expiredOrders = $this->fetchExpiredOrders();

        foreach ($expiredOrders as $order) {
            $this->checkAndUpdateOrder($order);
        }

        return Command::SUCCESS;
    }

    /**
     * @return PayOrder[]
     */
    private function fetchExpiredOrders(): array
    {
        $qb = $this->payOrderRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.expireTime < :now')
            ->setParameter('status', PayOrderStatus::INIT)
            ->setParameter('now', CarbonImmutable::now())
        ;

        $results = $qb->getQuery()->getResult();
        if (!is_array($results)) {
            return [];
        }

        return array_filter($results, fn ($row) => $row instanceof PayOrder);
    }

    private function checkAndUpdateOrder(PayOrder $order): void
    {
        $merchant = $order->getMerchant();
        if (null === $merchant) {
            $this->logger->error('PayOrder merchant is null', ['trade_no' => $order->getTradeNo()]);

            return;
        }

        $builder = $this->wechatPayBuilder->genBuilder($merchant);
        $startTime = microtime(true);

        try {
            $responseData = $this->queryOrderStatus($order, $builder, $startTime);
            $this->updateOrderFromResponse($order, $responseData);
        } catch (\Throwable $e) {
            $this->logQueryError($order, $e, $startTime);
        }
    }

    /**
     * @param BuilderChainable $builder
     * @return array<string, mixed>
     */
    private function queryOrderStatus(PayOrder $order, $builder, float $startTime): array
    {
        $this->logger->info('开始查询微信支付订单状态', [
            'trade_no' => $order->getTradeNo(),
            'mch_id' => $order->getMchId(),
        ]);

        $chainable = $builder->chain("v3/pay/transactions/out-trade-no/{$order->getTradeNo()}?mchid={$order->getMchId()}");
        $response = $chainable->get();
        $body = $response->getBody();
        $contents = $body->getContents();
        /** @var array<string, mixed> $responseData */
        $responseData = Json::decode($contents);
        $endTime = microtime(true);

        $this->logger->info('查询订单流水状态成功', [
            'trade_no' => $order->getTradeNo(),
            'response' => $responseData,
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
        ]);

        return $responseData;
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function updateOrderFromResponse(PayOrder $order, array $responseData): void
    {
        $transactionId = isset($responseData['transaction_id']) && \is_string($responseData['transaction_id']) ? $responseData['transaction_id'] : null;
        $order->setTransactionId($transactionId);

        $tradeType = \is_string($responseData['trade_type'] ?? null) ? $responseData['trade_type'] : '';
        $order->setTradeType($tradeType);

        $tradeState = isset($responseData['trade_state']) && \is_string($responseData['trade_state']) ? $responseData['trade_state'] : null;
        $order->setTradeState($tradeState);

        $attach = isset($responseData['attach']) && \is_string($responseData['attach']) ? $responseData['attach'] : null;
        $order->setAttach($attach);

        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $this->entityManager->detach($order);
    }

    private function logQueryError(PayOrder $order, \Throwable $e, float $startTime): void
    {
        $endTime = microtime(true);
        $this->logger->error('查询订单流水状态失败', [
            'trade_no' => $order->getTradeNo(),
            'exception' => $e->getMessage(),
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
        ]);
    }
}
