<?php

namespace WechatPayBundle\Controller;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\XML\XML;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Event\JSAPIPayCallbackSuccessEvent;
use WechatPayBundle\Event\NativePayCallbackSuccessEvent;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Repository\PayOrderRepository;
use Yiisoft\Json\Json;

#[Route(path: '/wechat-payment/unified-order')]
class UnifiedOrderController extends AbstractController
{
    /**
     * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_5_5.shtml
     */
    #[Route(path: '/pay/{traderNo}', name: 'wechat_app_unified_order_pay_callback', methods: ['POST'])]
    public function payCallback(
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

            return new Response(XML::build([
                'return_code' => 'FAIL',
                'return_msg' => '通知过于频繁',
            ]));
        }
        try {
            $payOrder = $payOrderRepository->findOneBy(['tradeNo' => $traderNo]);
            if (!$payOrder) {
                return new Response(XML::build([
                    'return_code' => 'FAIL',
                    'return_msg' => '订单不存在',
                ]));
            }
            if (PayOrderStatus::SUCCESS === $payOrder->getStatus()) {
                return new Response(XML::build([
                    'return_code' => 'SUCCESS',
                    'return_msg' => '订单已处理',
                ]));
            }
            $body = $request->getContent();
            $logger->info('支付回调', [
                'data' => $body,
            ]);
            $attributes = XML::parse($body);
            $logger->info('格式化数据', [
                'xml' => $attributes,
            ]);

            // 将回调信息存起来
            $payOrder->setCallbackResponse(Json::encode($attributes));
            $payOrder->setCallbackTime(Carbon::now());
            if (isset($attributes['transaction_id'])) {
                $payOrder->setTransactionId($attributes['transaction_id']);
            }
            $entityManager->persist($payOrder);
            $entityManager->flush();
            $merchant = $merchantRepository->findOneBy([
                'mchId' => $attributes['mch_id'],
            ]);
            if (!$merchant) {
                return new Response(XML::build([
                    'return_code' => 'FAIL',
                    'return_msg' => '商户号错误',
                ]));
            }
            $sign = $attributes['sign'];
            unset($attributes['sign']);
            // 进行签名
            $currentSign = $this->generateSign($attributes, $merchant->getPemKey());

            if ($sign != $currentSign) {
                return new Response(XML::build([
                    'return_code' => 'FAIL',
                    'return_msg' => '签名验证失败',
                ]));
            }

            // 只要签名过了就算通知成功了，至于事件下面的逻辑，各自需要处理好
            $payOrder->setStatus(PayOrderStatus::SUCCESS);
            $entityManager->persist($payOrder);
            $entityManager->flush();
            switch ($payOrder->getTradeType()) {
                case 'JSAPI':
                    $successEvent = new JSAPIPayCallbackSuccessEvent();
                    $successEvent->setPayOrder($payOrder);
                    $successEvent->setPayload($attributes);
                    $eventDispatcher->dispatch($successEvent);
                    break;
                case 'NATIVE':
                    $successEvent = new NativePayCallbackSuccessEvent();
                    $successEvent->setPayOrder($payOrder);
                    $successEvent->setDecryptData($attributes);
                    $eventDispatcher->dispatch($successEvent);
                    break;
            }
        } catch (\Throwable $exception) {
            $logger->error("处理微信app支付回调事件失败:{$traderNo}", [
                'error' => $exception,
            ]);
        } finally {
            $lock->release();
        }

        return new Response(XML::build([
            'return_code' => 'SUCCESS',
            'return_msg' => 'ok',
        ]));
    }

    /**
     * Generate a signature.
     *
     * @param string $key
     * @param string $encryptMethod
     */
    public function generateSign(array $attributes, $key, $encryptMethod = 'md5'): string
    {
        ksort($attributes);

        $attributes['key'] = $key;

        return mb_strtoupper((string) call_user_func_array($encryptMethod, [urldecode(http_build_query($attributes))]));
    }
}
