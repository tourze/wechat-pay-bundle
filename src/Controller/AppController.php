<?php

namespace WechatPayBundle\Controller;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\XML\XML;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Event\AppPayCallbackSuccessEvent;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Repository\PayOrderRepository;
use Yiisoft\Json\Json;

#[WithMonologChannel(channel: 'wechat_pay')]
final class AppController extends AbstractController
{
    /**
     * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_5_5.shtml
     */
    #[Route(path: '/wechat-payment/app/pay/{traderNo}', name: 'wechat_app_pay_callback', methods: ['POST'])]
    public function __invoke(
        string $traderNo,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        PayOrderRepository $payOrderRepository,
        MerchantRepository $merchantRepository,
        LockFactory $lockFactory,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        $lock = $lockFactory->createLock("wechat-app-pay-success-{$traderNo}");
        if (!$lock->acquire()) {
            $logger->error("获取锁失败:{$traderNo}");

            return $this->buildFailResponse('通知过于频繁');
        }
        try {
            return $this->processPayment(
                $traderNo,
                $logger,
                $eventDispatcher,
                $payOrderRepository,
                $merchantRepository,
                $entityManager,
                $request
            );
        } catch (\Throwable $exception) {
            $logger->error("处理微信app支付回调事件失败:{$traderNo}", [
                'error' => $exception,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->buildFailResponse('处理失败');
        } finally {
            $lock->release();
        }
    }

    private function processPayment(
        string $traderNo,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        PayOrderRepository $payOrderRepository,
        MerchantRepository $merchantRepository,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        $payOrder = $payOrderRepository->findOneBy(['tradeNo' => $traderNo]);
        if (null === $payOrder) {
            return $this->buildFailResponse('订单不存在');
        }

        if (PayOrderStatus::SUCCESS === $payOrder->getStatus()) {
            return $this->buildSuccessResponse('订单已处理');
        }

        $body = $request->getContent();
        $logger->info('支付回调', ['data' => $body]);

        $attributes = $this->parseXmlBody($body, $logger);
        if (null === $attributes) {
            return $this->buildFailResponse('XML格式错误');
        }

        $logger->info('格式化数据', ['xml' => $attributes]);

        $this->saveCallbackData($payOrder, $attributes, $entityManager);

        $validation = $this->validateSignature($attributes, $merchantRepository);
        if ($validation['response'] instanceof Response) {
            return $validation['response'];
        }
        $attributes = $validation['attributes'];

        $this->markOrderSuccess($payOrder, $entityManager);
        $this->dispatchSuccessEvent($payOrder, $attributes, $eventDispatcher);

        return $this->buildSuccessResponse('ok');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseXmlBody(string $body, LoggerInterface $logger): ?array
    {
        try {
            return XML::parse($body);
        } catch (\Throwable $parseException) {
            $logger->error('XML 解析失败', [
                'body' => $body,
                'error' => $parseException->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function saveCallbackData(PayOrder $payOrder, array $attributes, EntityManagerInterface $entityManager): void
    {
        $payOrder->setCallbackResponse(Json::encode($attributes));
        $payOrder->setCallbackTime(CarbonImmutable::now());
        if (isset($attributes['transaction_id'])) {
            $transactionId = \is_string($attributes['transaction_id']) ? $attributes['transaction_id'] : null;
            $payOrder->setTransactionId($transactionId);
        }
        $entityManager->persist($payOrder);
        $entityManager->flush();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array{response: Response|null, attributes: array<string, mixed>}
     */
    private function validateSignature(array $attributes, MerchantRepository $merchantRepository): array
    {
        $merchant = $merchantRepository->findOneBy(['mchId' => $attributes['mch_id']]);
        if (null === $merchant) {
            return ['response' => $this->buildFailResponse('商户号错误'), 'attributes' => $attributes];
        }

        $pemKey = $merchant->getPemKey();
        if (null === $pemKey) {
            return ['response' => $this->buildFailResponse('商户私钥缺失'), 'attributes' => $attributes];
        }

        $sign = $attributes['sign'];
        unset($attributes['sign']);
        $currentSign = $this->generateSign($attributes, $pemKey);

        if ($sign !== $currentSign) {
            return ['response' => $this->buildFailResponse('签名验证失败'), 'attributes' => $attributes];
        }

        return ['response' => null, 'attributes' => $attributes];
    }

    private function markOrderSuccess(PayOrder $payOrder, EntityManagerInterface $entityManager): void
    {
        $payOrder->setStatus(PayOrderStatus::SUCCESS);
        $entityManager->persist($payOrder);
        $entityManager->flush();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function dispatchSuccessEvent(
        PayOrder $payOrder,
        array $attributes,
        EventDispatcherInterface $eventDispatcher,
    ): void {
        $successEvent = new AppPayCallbackSuccessEvent();
        $successEvent->setPayOrder($payOrder);
        $successEvent->setDecryptData($attributes);
        $eventDispatcher->dispatch($successEvent);
    }

    private function buildFailResponse(string $message): Response
    {
        return new Response(XML::build([
            'return_code' => 'FAIL',
            'return_msg' => $message,
        ]));
    }

    private function buildSuccessResponse(string $message): Response
    {
        return new Response(XML::build([
            'return_code' => 'SUCCESS',
            'return_msg' => $message,
        ]));
    }

    /**
     * 生成签名
     *
     * @param array<string, mixed> $attributes
     * @param string $key
     * @param string $encryptMethod
     */
    public function generateSign(array $attributes, string $key, string $encryptMethod = 'md5'): string
    {
        ksort($attributes);

        $attributes['key'] = $key;

        $queryString = urldecode(http_build_query($attributes));

        return match ($encryptMethod) {
            'md5' => strtoupper(md5($queryString)),
            'sha1' => strtoupper(sha1($queryString)),
            default => strtoupper(hash($encryptMethod, $queryString)),
        };
    }
}
