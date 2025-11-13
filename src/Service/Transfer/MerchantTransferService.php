<?php

declare(strict_types=1);

namespace WechatPayBundle\Service\Transfer;

use Monolog\Attribute\WithMonologChannel;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use WeChatPay\BuilderChainable;
use WeChatPay\Crypto\Rsa;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Exception\MerchantTransferException;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[WithMonologChannel(channel: 'wechat_pay')]
final class MerchantTransferService implements MerchantTransferServiceInterface
{
    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly WechatPayBuilder $wechatPayBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function transfer(MerchantTransferCommand $command): MerchantTransferResult
    {
        $merchant = $this->resolveMerchant($command->getMerchantId());
        $payload = $this->buildPayload($merchant, $command);

        $this->logger->info('发起微信提现', [
            'out_batch_no' => $command->getOutBatchNo(),
            'out_detail_no' => $command->getOutDetailNo(),
            'merchant' => $merchant->getMchId(),
            'amount_fen' => $command->getAmount(),
        ]);

        try {
            $builder = $this->wechatPayBuilder->genBuilder($merchant);
            $response = $this->sendRequest($builder, $merchant, $payload);

            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $data */
            $data = Json::decode($body);
            $detail = $this->extractDetail($data);

            $result = new MerchantTransferResult(
                batchId: (string) ($data['batch_id'] ?? ''),
                batchStatus: (string) ($data['batch_status'] ?? ''),
                createTime: isset($data['create_time']) ? (string) $data['create_time'] : null,
                outBatchNo: (string) ($data['out_batch_no'] ?? $command->getOutBatchNo()),
                detailId: isset($detail['detail_id']) ? (string) $detail['detail_id'] : null,
                detailStatus: isset($detail['detail_status']) ? (string) $detail['detail_status'] : null,
                outDetailNo: isset($detail['out_detail_no']) ? (string) $detail['out_detail_no'] : $command->getOutDetailNo(),
            );

            $this->logger->info('微信提现响应', [
                'out_batch_no' => $result->getOutBatchNo(),
                'batch_id' => $result->getBatchId(),
                'detail_status' => $result->getDetailStatus(),
            ]);

            return $result;
        } catch (\Throwable $exception) {
            $this->logger->error('微信提现失败', [
                'out_batch_no' => $command->getOutBatchNo(),
                'out_detail_no' => $command->getOutDetailNo(),
                'error' => $exception->getMessage(),
            ]);

            throw new MerchantTransferException('微信商家转账请求失败：' . $exception->getMessage(), 0, $exception);
        }
    }

    private function resolveMerchant(?string $merchantId): Merchant
    {
        if (null !== $merchantId && '' !== $merchantId) {
            $merchant = $this->merchantRepository->findOneBy(['mchId' => $merchantId]);
            if (null === $merchant) {
                throw new MerchantTransferException(sprintf('未找到商户号：%s', $merchantId));
            }

            return $merchant;
        }

        $merchant = $this->merchantRepository->findOneBy(['valid' => true], ['id' => 'DESC']);
        if (null !== $merchant) {
            return $merchant;
        }

        $merchant = $this->merchantRepository->findOneBy([], ['id' => 'DESC']);
        if (null === $merchant) {
            throw new MerchantTransferException('尚未配置任何微信商户。');
        }

        return $merchant;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendRequest(BuilderChainable $builder, Merchant $merchant, array $payload): ResponseInterface
    {
        return $builder->chain('v3/transfer/batches')->post([
            'headers' => [
                'Wechatpay-Serial' => $this->wechatPayBuilder->getPlatformCertificateSerial($merchant),
            ],
            'json' => $payload,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Merchant $merchant, MerchantTransferCommand $command): array
    {
        $detail = [
            'out_detail_no' => $command->getOutDetailNo(),
            'transfer_amount' => $command->getAmount(),
            'transfer_remark' => $command->getTransferRemark(),
            'openid' => $command->getOpenid(),
        ];

        $userName = $command->getUserName();
        if (null !== $userName && '' !== $userName) {
            $detail['user_name'] = Rsa::encrypt($userName, $this->wechatPayBuilder->getPlatformPublicKey($merchant));
        }

        return [
            'appid' => $command->getAppId(),
            'out_batch_no' => $command->getOutBatchNo(),
            'batch_name' => $command->getBatchName(),
            'batch_remark' => $command->getBatchRemark(),
            'total_amount' => $command->getAmount(),
            'total_num' => 1,
            'transfer_detail_list' => [$detail],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function extractDetail(array $data): array
    {
        $detailList = $data['transfer_detail_list'] ?? [];
        if (!\is_array($detailList) || !isset($detailList[0]) || !\is_array($detailList[0])) {
            return [];
        }

        /** @var array<string, mixed> $detail */
        $detail = $detailList[0];

        return $detail;
    }
}
