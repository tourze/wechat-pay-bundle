<?php

namespace WechatPayBundle\Service;

use BaconQrCodeBundle\Service\QrcodeService;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use HttpClientBundle\Service\SmartHttpClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\XML\XML;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Request\AppOrderParams;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Json\Json;

class UnifiedOrder
{
    protected string $tradeType = '';

    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly LoggerInterface $logger,
        private readonly SmartHttpClient $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly QrcodeService $qrcodeService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createH5Order(AppOrderParams $appOrderParams): array
    {
        if (!$this->tradeType) {
            throw new \Exception('请设置下单类型');
        }

        $merchant = $this->merchantRepository->findOneBy([
            'mchId' => $appOrderParams->getMchId(),
        ]);

        $appid = $appOrderParams->getAppId();
        $attach = $appOrderParams->getAttach();
        $description = $appOrderParams->getDescription();
        $payOrder = new PayOrder();
        $payOrder->setMerchant($merchant);
        $payOrder->setStatus(PayOrderStatus::INIT);
        $payOrder->setBody($description); // 公众号appID
        $payOrder->setAppId($appOrderParams->getAppId()); // 公众号appID
        $payOrder->setMchId($appOrderParams->getMchId());
        $payOrder->setTradeType($this->tradeType);
        $payOrder->setTradeNo($appOrderParams->getContractId());
        $payOrder->setAttach($attach);
        $payOrder->setCreateIp($this->requestStack->getCurrentRequest()->getClientIp());
        if ($appOrderParams->getOpenId()) {
            $payOrder->setOpenId($appOrderParams->getOpenId());
        }

        $startTime = Carbon::now();
        // 一般是15分钟后过期
        $expireTime = $startTime->clone()->addMinutes(15);
        $payOrder->setStartTime($startTime);
        $payOrder->setExpireTime($expireTime);

        // 费用情况
        $payOrder->setTotalFee($appOrderParams->getMoney());
        $payOrder->setFeeType($appOrderParams->getCurrency());

        // 支付者信息
        $payOrder->setOpenId('');

        // 回调地址也保存起来吧 https://api3-staging.mixpwr.com/
        $payOrder->setNotifyUrl($this->urlGenerator->generate('wechat_app_unified_order_pay_callback', [
            'traderNo' => $payOrder->getTradeNo(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));

        // 保存支付订单
        $this->entityManager->persist($payOrder);
        $this->entityManager->flush();

        // 调用远程接口统一下单
        $requestJson = [
            'body' => $description,
            'appid' => $appid,
            'mch_id' => $appOrderParams->getMchId(),
            'trade_type' => $this->tradeType,
            'nonce_str' => uniqid(),
            'out_trade_no' => $payOrder->getTradeNo(),
            'time_expire' => $payOrder->getExpireTime()->format('YmdHis'),
            'notify_url' => $payOrder->getNotifyUrl(),
            'total_fee' => $payOrder->getTotalFee(),
            'spbill_create_ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
        ];
        if ($appOrderParams->getOpenId()) {
            $requestJson['openid'] = $appOrderParams->getOpenId();
        }
        if ($payOrder->getAttach()) {
            $requestJson['attach'] = $payOrder->getAttach();
        }
        $payOrder->setRequestJson(Json::encode($requestJson));
        $sign = $this->generateSign($requestJson, $_ENV['MC_WECHAT_H5_PAYMENT_PEM_KEY'] ?? '7rPb3kLx9gNzQsA6tD2F8jYhV5mEwXxP');
        $requestJson['sign'] = $sign;
        $postXml = XML::build($requestJson);
        $this->logger->info('统一下单参数', [
            'attributes' => $requestJson,
            'xml' => $postXml,
        ]);
        $response = $this->httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/unifiedorder', [
            'body' => $postXml,
        ]);
        $json = $response->getContent(false);
        $json = XML::parse($json);
        $this->logger->info('下单结果', [
            'json' => $json,
        ]);
        $prepayId = ArrayHelper::getValue($json, 'prepay_id');
        if (!$prepayId) {
            throw new \Exception('获取微信APP支付关键参数出错');
        }

        // 创建返回给客户端的数据
        $params = [
            'code_url' => $this->qrcodeService->getImageUrl($json['code_url']),
            'timeStamp' => strval(Carbon::now()->getTimestamp()),
            'createDate' => date('Y-m-d H:i:s', time()),
            'description' => $description,
            'payOrderId' => $payOrder->getId(),
            'tradeNo' => $payOrder->getTradeNo(),
            'nonceStr' => $json['nonce_str'],
            'package' => "prepay_id={$prepayId}",
            'signType' => 'MD5',
        ];

        $params['retmsg'] = 'ok';

        return $params;
    }

    public function createAppOrder(AppOrderParams $appOrderParams): array
    {
        if (!$this->tradeType) {
            throw new \Exception('请设置下单类型');
        }
        // 如果没声明，我们就取第一个支付配置
        if (empty($appOrderParams->getMchId())) {
            $merchant = $this->merchantRepository->findOneBy([], ['id' => 'DESC']);
        } else {
            $merchant = $this->merchantRepository->findOneBy([
                'mchId' => $appOrderParams->getMchId(),
            ]);
        }
        $appid = $appOrderParams->getAppId();
        $mchId = $appOrderParams->getMchId();
        $attach = $appOrderParams->getAttach();
        $description = $appOrderParams->getDescription();
        $payOrder = new PayOrder();
        $payOrder->setMerchant($merchant);
        $payOrder->setStatus(PayOrderStatus::INIT);
        $payOrder->setBody($description); // 公众号appID
        $payOrder->setAppId(''); // 公众号appID
        $payOrder->setMchId($merchant->getMchId());
        $payOrder->setTradeType($this->tradeType);
        $payOrder->setTradeNo($appOrderParams->getContractId());
        $payOrder->setAttach($attach);
        $payOrder->setCreateIp($this->requestStack->getCurrentRequest()->getClientIp());
        if ($appOrderParams->getOpenId()) {
            $payOrder->setOpenId($appOrderParams->getOpenId());
        }

        $startTime = Carbon::now();
        // 一般是15分钟后过期
        $expireTime = $startTime->clone()->addMinutes(15);
        $payOrder->setStartTime($startTime);
        $payOrder->setExpireTime($expireTime);

        // 费用情况
        $payOrder->setTotalFee($appOrderParams->getMoney());
        $payOrder->setFeeType($appOrderParams->getCurrency());

        // 支付者信息
        $payOrder->setOpenId('');

        // 回调地址也保存起来吧 https://api3-staging.mixpwr.com/
        $payOrder->setNotifyUrl($this->urlGenerator->generate('wechat_app_unified_order_pay_callback', [
            'traderNo' => $payOrder->getTradeNo(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));

        // 保存支付订单
        $this->entityManager->persist($payOrder);
        $this->entityManager->flush();

        // 调用远程接口统一下单
        $requestJson = [
            'body' => $description,
            'appid' => $appid,
            'mch_id' => $merchant->getMchId(),
            'trade_type' => $this->tradeType,
            'nonce_str' => uniqid(),
            'out_trade_no' => $payOrder->getTradeNo(),
            'time_expire' => $payOrder->getExpireTime()->format('YmdHis'),
            'notify_url' => $payOrder->getNotifyUrl(),
            'total_fee' => $payOrder->getTotalFee(),
            'spbill_create_ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
        ];
        if ($appOrderParams->getOpenId()) {
            $requestJson['openid'] = $appOrderParams->getOpenId();
        }
        if ($payOrder->getAttach()) {
            $requestJson['attach'] = $payOrder->getAttach();
        }
        $payOrder->setRequestJson(Json::encode($requestJson));
        $sign = $this->generateSign($requestJson, $merchant->getPemKey());
        $requestJson['sign'] = $sign;
        $postXml = XML::build($requestJson);
        $this->logger->info('统一下单参数', [
            'attributes' => $requestJson,
            'xml' => $postXml,
        ]);
        $response = $this->httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/unifiedorder', [
            'body' => $postXml,
        ]);
        $json = $response->getContent(false);
        $json = XML::parse($json);
        $this->logger->info('下单结果', [
            'json' => $json,
        ]);
        $prepayId = ArrayHelper::getValue($json, 'prepay_id');
        if (!$prepayId) {
            throw new \Exception('获取微信APP支付关键参数出错');
        }

        // 创建返回给客户端的数据
        $params = [
            'appId' => $appid,
            'timeStamp' => strval(Carbon::now()->getTimestamp()),
            'nonceStr' => $json['nonce_str'],
            'package' => "prepay_id={$prepayId}",
            'signType' => 'MD5',
        ];
        $params['paySign'] = $this->generateSign($params, $merchant->getPemKey());

        $params['retmsg'] = 'ok';

        return $params;
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

        return strtoupper((string) call_user_func_array($encryptMethod, [urldecode(http_build_query($attributes))]));
    }
}
