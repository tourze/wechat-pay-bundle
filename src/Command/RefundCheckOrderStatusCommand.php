<?php

namespace WechatPayBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
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
#[AsCronTask('* * * * *')]
#[AsCommand(name: 'wechat:refund:check-order-status', description: '检查退款订单状态')]
class RefundCheckOrderStatusCommand extends Command
{
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
            ->setParameter('status', 'PROCESSING');

        foreach ($qb->getQuery()->toIterable() as $row) {
            /** @var RefundOrder $row */
            $builder = $this->wechatPayBuilder->genBuilder($row->getPayOrder()->getMerchant());
            $response = $builder->chain("v3/refund/domestic/refunds/{$row->getId()}")->get();
            $response = $response->getBody()->getContents();
            $row->setResponseJson($response);

            $response = Json::decode($response);
            $this->logger->info('查询订单退款状态', $response);

            if (!isset($response['refund_id'])) {
                // 订单不存在？或者有报错
                $row->setStatus('CLOSED');
                $this->entityManager->persist($row);
                $this->entityManager->flush();
                continue;
            }

            // 保存数据咯
            $row->processResponseData($response);
            $this->entityManager->persist($row);
            $this->entityManager->flush();
            $this->entityManager->detach($row);
        }

        return Command::SUCCESS;
    }
}
