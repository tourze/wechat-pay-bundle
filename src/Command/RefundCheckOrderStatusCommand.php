<?php

namespace WechatPayBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use WechatPayBundle\Entity\RefundOrder;
use WechatPayBundle\Repository\RefundOrderRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，建议在提交退款申请后1分钟发起查询退款状态，一般来说零钱支付的退款5分钟内到账，银行卡支付的退款1-3个工作日到账。
 *
 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_10.shtml
 */
#[AsCronTask(expression: '* * * * *')]
#[AsCommand(name: self::NAME, description: '检查退款订单状态')]
#[WithMonologChannel(channel: 'wechat_pay')]
class RefundCheckOrderStatusCommand extends Command
{
    public const NAME = 'wechat:refund:check-order-status';

    public function __construct(
        private readonly RefundOrderRepository $refundOrderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WechatPayBuilder $wechatPayBuilder,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $qb = $this->refundOrderRepository->createQueryBuilder('a')
            ->where('a.status IS NULL')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'PROCESSING')
        ;

        foreach ($qb->getQuery()->toIterable() as $row) {
            /** @var RefundOrder $row */
            $payOrder = $row->getPayOrder();
            if (null === $payOrder) {
                $this->logger->error('RefundOrder payOrder is null', ['refund_id' => $row->getId()]);
                continue;
            }
            $merchant = $payOrder->getMerchant();
            if (null === $merchant) {
                $this->logger->error('PayOrder merchant is null', ['refund_id' => $row->getId()]);
                continue;
            }
            $builder = $this->wechatPayBuilder->genBuilder($merchant);
            $startTime = microtime(true);
            try {
                $tradeNo = $payOrder->getTradeNo();
                if (null === $tradeNo) {
                    $this->logger->error('PayOrder tradeNo is null', ['refund_id' => $row->getId()]);
                    continue;
                }
                $this->logger->info('开始查询微信退款订单状态', [
                    'refund_id' => $row->getId(),
                    'out_trade_no' => $tradeNo,
                ]);
                $response = $builder->chain("v3/refund/domestic/refunds/{$row->getId()}")->get();
                $response = $response->getBody()->getContents();
                $row->setResponseJson($response);

                /** @var array<string, mixed> $responseData */
                $responseData = Json::decode($response);
                $endTime = microtime(true);

                $this->logger->info('查询订单退款状态成功', [
                    'refund_id' => $row->getId(),
                    'response' => $responseData,
                    'duration_ms' => round(($endTime - $startTime) * 1000, 2),
                ]);

                if (!isset($responseData['refund_id'])) {
                    // 订单不存在？或者有报错
                    $row->setStatus('CLOSED');
                    $this->entityManager->persist($row);
                    $this->entityManager->flush();
                    continue;
                }

                // 保存数据咯
                $row->processResponseData($responseData);
                $this->entityManager->persist($row);
                $this->entityManager->flush();
                $this->entityManager->detach($row);
            } catch (\Throwable $e) {
                $endTime = microtime(true);
                $this->logger->error('查询订单退款状态失败', [
                    'refund_id' => $row->getId(),
                    'exception' => $e->getMessage(),
                    'duration_ms' => round(($endTime - $startTime) * 1000, 2),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
