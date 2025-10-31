<?php

namespace WechatPayBundle\Service;

use BaconQrCodeBundle\Service\QrcodeService;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use HttpClientBundle\Service\SmartHttpClient;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\XML\XML;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Exception\CryptographyException;
use WechatPayBundle\Exception\InvalidTradeTypeException;
use WechatPayBundle\Exception\MerchantConfigurationException;
use WechatPayBundle\Exception\PaymentParameterException;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Request\AppOrderParams;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Json\Json;

#[WithMonologChannel(channel: 'wechat_pay')]
abstract class UnifiedOrder
{
    protected string $tradeType = '';

    public function setTradeType(string $tradeType): void
    {
        $this->tradeType = $tradeType;
    }

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

    /**
     * @return array<string, mixed>
     */
    public function createH5Order(AppOrderParams $appOrderParams): array
    {
        if ('' === $this->tradeType) {
            throw new InvalidTradeTypeException('请设置下单类型');
        }

        /** @var Merchant|null $merchant */
        $merchant = $this->merchantRepository->findOneBy([
            'mchId' => $appOrderParams->getMchId(),
        ]);

        $appid = $appOrderParams->getAppId();
        $attach = $appOrderParams->getAttach();
        $description = $appOrderParams->getDescription();
        $payOrder = new PayOrder();
        if (null !== $merchant) {
            $payOrder->setMerchant($merchant);
        }
        $payOrder->setStatus(PayOrderStatus::INIT);
        $payOrder->setBody($description); // 公众号appID
        $payOrder->setAppId($appOrderParams->getAppId()); // 公众号appID
        $payOrder->setMchId($appOrderParams->getMchId());
        $payOrder->setTradeType($this->tradeType);
        $payOrder->setTradeNo($appOrderParams->getContractId());
        $payOrder->setAttach($attach);
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null !== $currentRequest) {
            $payOrder->setCreatedFromIp($currentRequest->getClientIp());
        }
        $openId = $appOrderParams->getOpenId();
        if ('' !== $openId) {
            $payOrder->setOpenId($openId);
        }

        $startTime = CarbonImmutable::now();
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
            'time_expire' => null !== $payOrder->getExpireTime() ? $payOrder->getExpireTime()->format('YmdHis') : '',
            'notify_url' => $payOrder->getNotifyUrl(),
            'total_fee' => $payOrder->getTotalFee(),
            'spbill_create_ip' => null !== $currentRequest ? $currentRequest->getClientIp() : '127.0.0.1',
        ];
        $openId = $appOrderParams->getOpenId();
        if ('' !== $openId) {
            $requestJson['openid'] = $openId;
        }
        $attach = $payOrder->getAttach();
        if ('' !== $attach) {
            $requestJson['attach'] = $attach;
        }
        $payOrder->setRequestJson(Json::encode($requestJson));
        $key = $_ENV['MC_WECHAT_H5_PAYMENT_PEM_KEY'] ?? '7rPb3kLx9gNzQsA6tD2F8jYhV5mEwXxP';
        \assert(\is_string($key));
        $sign = $this->generateSign($requestJson, $key);
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
        if (null === $prepayId || '' === $prepayId) {
            throw new PaymentParameterException('获取微信APP支付关键参数出错');
        }

        \assert(\is_string($prepayId));
        $codeUrl = ArrayHelper::getValue($json, 'code_url');
        \assert(\is_string($codeUrl));
        $nonceStr = ArrayHelper::getValue($json, 'nonce_str');
        \assert(\is_string($nonceStr));

        // 创建返回给客户端的数据
        $params = [
            'code_url' => $this->qrcodeService->getImageUrl($codeUrl),
            'timeStamp' => strval(CarbonImmutable::now()->getTimestamp()),
            'createDate' => date('Y-m-d H:i:s', time()),
            'description' => $description,
            'payOrderId' => $payOrder->getId(),
            'tradeNo' => $payOrder->getTradeNo(),
            'nonceStr' => $nonceStr,
            'package' => "prepay_id={$prepayId}",
            'signType' => 'MD5',
        ];

        $params['retmsg'] = 'ok';

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    public function createAppOrder(AppOrderParams $appOrderParams): array
    {
        if ('' === $this->tradeType) {
            throw new InvalidTradeTypeException('请设置下单类型');
        }

        $merchant = $this->findMerchant($appOrderParams->getMchId());
        $payOrder = $this->createPayOrder($appOrderParams, $merchant);
        $wechatResponse = $this->callWechatUnifiedOrder($appOrderParams, $payOrder, $merchant);

        return $this->buildClientResponse($appOrderParams->getAppId(), $wechatResponse, $merchant);
    }

    private function findMerchant(?string $mchId): Merchant
    {
        if (null === $mchId || '' === $mchId) {
            /** @var Merchant|null $merchant */
            $merchant = $this->merchantRepository->findOneBy([], ['id' => 'DESC']);
        } else {
            /** @var Merchant|null $merchant */
            $merchant = $this->merchantRepository->findOneBy(['mchId' => $mchId]);
        }

        if (null === $merchant) {
            throw new MerchantConfigurationException('商户配置不存在');
        }

        return $merchant;
    }

    private function createPayOrder(AppOrderParams $appOrderParams, Merchant $merchant): PayOrder
    {
        $payOrder = new PayOrder();
        $payOrder->setMerchant($merchant);
        $payOrder->setStatus(PayOrderStatus::INIT);
        $payOrder->setBody($appOrderParams->getDescription());
        $payOrder->setAppId('');
        $payOrder->setMchId($merchant->getMchId());
        $payOrder->setTradeType($this->tradeType);
        $payOrder->setTradeNo($appOrderParams->getContractId());
        $payOrder->setAttach($appOrderParams->getAttach());

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null !== $currentRequest) {
            $payOrder->setCreatedFromIp($currentRequest->getClientIp());
        }

        $openId = $appOrderParams->getOpenId();
        if ('' !== $openId) {
            $payOrder->setOpenId($openId);
        }

        $startTime = CarbonImmutable::now();
        $expireTime = $startTime->clone()->addMinutes(15);
        $payOrder->setStartTime($startTime);
        $payOrder->setExpireTime($expireTime);
        $payOrder->setTotalFee($appOrderParams->getMoney());
        $payOrder->setFeeType($appOrderParams->getCurrency());
        $payOrder->setOpenId('');
        $payOrder->setNotifyUrl($this->urlGenerator->generate('wechat_app_unified_order_pay_callback', [
            'traderNo' => $payOrder->getTradeNo(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));

        $this->entityManager->persist($payOrder);
        $this->entityManager->flush();

        return $payOrder;
    }

    /**
     * @return array<string, mixed>
     */
    private function callWechatUnifiedOrder(AppOrderParams $appOrderParams, PayOrder $payOrder, Merchant $merchant): array
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $requestJson = [
            'body' => $appOrderParams->getDescription(),
            'appid' => $appOrderParams->getAppId(),
            'mch_id' => $merchant->getMchId(),
            'trade_type' => $this->tradeType,
            'nonce_str' => uniqid(),
            'out_trade_no' => $payOrder->getTradeNo(),
            'time_expire' => null !== $payOrder->getExpireTime() ? $payOrder->getExpireTime()->format('YmdHis') : '',
            'notify_url' => $payOrder->getNotifyUrl(),
            'total_fee' => $payOrder->getTotalFee(),
            'spbill_create_ip' => null !== $currentRequest ? $currentRequest->getClientIp() : '127.0.0.1',
        ];

        $openId = $appOrderParams->getOpenId();
        if ('' !== $openId) {
            $requestJson['openid'] = $openId;
        }

        $attach = $payOrder->getAttach();
        if ('' !== $attach) {
            $requestJson['attach'] = $attach;
        }

        $payOrder->setRequestJson(Json::encode($requestJson));
        $pemKey = $merchant->getPemKey();
        if (null === $pemKey) {
            throw new MerchantConfigurationException('商户私钥不能为空');
        }

        $sign = $this->generateSign($requestJson, $pemKey);
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
        if (null === $prepayId || '' === $prepayId) {
            throw new PaymentParameterException('获取微信APP支付关键参数出错');
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $wechatResponse
     * @return array<string, mixed>
     */
    private function buildClientResponse(string $appId, array $wechatResponse, Merchant $merchant): array
    {
        $prepayId = ArrayHelper::getValue($wechatResponse, 'prepay_id');
        \assert(\is_string($prepayId));
        $nonceStr = ArrayHelper::getValue($wechatResponse, 'nonce_str');
        \assert(\is_string($nonceStr));

        $params = [
            'appId' => $appId,
            'timeStamp' => strval(CarbonImmutable::now()->getTimestamp()),
            'nonceStr' => $nonceStr,
            'package' => "prepay_id={$prepayId}",
            'signType' => 'MD5',
        ];

        $pemKey = $merchant->getPemKey();
        if (null === $pemKey) {
            throw new MerchantConfigurationException('商户私钥不能为空');
        }

        $params['paySign'] = $this->generateSign($params, $pemKey);
        $params['retmsg'] = 'ok';

        return $params;
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

        if (!is_callable($encryptMethod)) {
            throw new CryptographyException("加密方法 {$encryptMethod} 不可调用");
        }

        $result = call_user_func($encryptMethod, urldecode(http_build_query($attributes)));
        if (!is_string($result)) {
            throw new CryptographyException('加密方法返回值必须是字符串');
        }

        return strtoupper($result);
    }
}
