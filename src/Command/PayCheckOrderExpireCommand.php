<?php

namespace WechatPayBundle\Command;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\PayOrderRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

/**
 * 根据微信的文档说明，对于那些久久没有回调的订单，我们需要定时任务去检查一下他们的状态
 *
 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_5_5.shtml
 */
#[AsCronTask('* * * * *')]
#[AsCommand(name: 'wechat:pay:check-order-expire', description: '检查订单过期状态')]
class PayCheckOrderExpireCommand extends Command
{
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
        $qb = $this->payOrderRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.expireTime < :now')
            ->setParameter('status', PayOrderStatus::INIT)
            ->setParameter('now', Carbon::now());

        foreach ($qb->getQuery()->getResult() as $row) {
            /** @var PayOrder $row */
            $builder = $this->wechatPayBuilder->genBuilder($row->getMerchant());
            $response = $builder->chain("v3/pay/transactions/out-trade-no/{$row->getTradeNo()}?mchid={$row->getMchId()}")->get();
            $response = $response->getBody()->getContents();
            $response = Json::decode($response);
            $this->logger->info('查询订单流水状态', $response);

            $row->setTransactionId($response['transaction_id']);
            $row->setTradeType($response['trade_type']);
            $row->setTradeState($response['trade_state']);
            $row->setAttach($response['attach']);
            $this->entityManager->persist($row);
            $this->entityManager->flush();
            $this->entityManager->detach($row);
        }

        return Command::SUCCESS;
    }
}
