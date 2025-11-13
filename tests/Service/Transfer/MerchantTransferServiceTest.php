<?php

declare(strict_types=1);

namespace WechatPayBundle\Tests\Service\Transfer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use WeChatPay\BuilderChainable;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Exception\MerchantTransferException;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Service\Transfer\MerchantTransferCommand;
use WechatPayBundle\Service\Transfer\MerchantTransferResult;
use WechatPayBundle\Service\Transfer\MerchantTransferService;
use WechatPayBundle\Service\Transfer\MerchantTransferServiceInterface;
use WechatPayBundle\Service\WechatPayBuilder;
use Yiisoft\Json\Json;

#[CoversClass(MerchantTransferService::class)]
final class MerchantTransferServiceTest extends TestCase
{
    public function testTransferReturnsResult(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('1900000109');
        $merchant->setCertSerial('merchant-serial');
        $merchant->setPemCert('-----BEGIN CERTIFICATE-----FAKE-----END CERTIFICATE-----');
        $merchant->setPemKey('-----BEGIN PRIVATE KEY-----FAKE-----END PRIVATE KEY-----');

        $repository = $this->createMock(MerchantRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['mchId' => '1900000109'])
            ->willReturn($merchant);

        $builderChainable = $this->createMock(BuilderChainable::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('getContents')->willReturn(Json::encode([
            'batch_id' => '123456',
            'batch_status' => 'ACCEPTED',
            'create_time' => '2024-01-01T00:00:00+08:00',
            'out_batch_no' => 'batch-no',
            'transfer_detail_list' => [[
                'detail_id' => 'detail-id',
                'detail_status' => 'SUCCESS',
                'out_detail_no' => 'detail-no',
            ]],
        ]));
        $response->method('getBody')->willReturn($stream);

        $builderChainable->expects($this->once())
            ->method('chain')
            ->with('v3/transfer/batches')
            ->willReturnSelf();
        $builderChainable->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $wechatPayBuilder = $this->createMock(WechatPayBuilder::class);
        $wechatPayBuilder->method('genBuilder')->willReturn($builderChainable);
        $wechatPayBuilder->method('getPlatformCertificateSerial')->willReturn('platform-serial');

        $logger = $this->createMock(LoggerInterface::class);

        $service = new MerchantTransferService($repository, $wechatPayBuilder, $logger);

        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-no',
            batchName: '佣金提现',
            batchRemark: '佣金提现',
            outDetailNo: 'detail-no',
            amount: 100,
            transferRemark: '提现',
            openid: 'openid-001',
            merchantId: '1900000109',
        );

        $result = $service->transfer($command);

        $this->assertInstanceOf(MerchantTransferResult::class, $result);
        $this->assertSame('123456', $result->getBatchId());
        $this->assertSame('detail-id', $result->getDetailId());
        $this->assertTrue($result->isDetailSuccessful());
    }

    public function testTransferThrowsWhenMerchantMissing(): void
    {
        $repository = $this->createMock(MerchantRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $wechatPayBuilder = $this->createMock(WechatPayBuilder::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new MerchantTransferService($repository, $wechatPayBuilder, $logger);

        $command = new MerchantTransferCommand(
            appId: 'wx1234567890',
            outBatchNo: 'batch-no',
            batchName: '佣金提现',
            batchRemark: '佣金提现',
            outDetailNo: 'detail-no',
            amount: 100,
            transferRemark: '提现',
            openid: 'openid-001',
            merchantId: '1900000109',
        );

        $this->expectException(MerchantTransferException::class);
        $service->transfer($command);
    }
}
