<?php

namespace WechatPayBundle\Service;

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
use WechatPayBundle\Repository\PayOrderRepository;
use WechatPayBundle\Request\AppOrderParams;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Json\Json;

class WechatAppPayService
{
    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly LoggerInterface $logger,
        private readonly SmartHttpClient $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PayOrderRepository $payOrderRepository,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createAppOrder(AppOrderParams $appOrderParams)
    {
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
        $payOrder->setAppId($appid); // 公众号appID
        $payOrder->setMchId($merchant->getMchId());
        $payOrder->setTradeType('APP');
        $payOrder->setTradeNo($appOrderParams->getContractId());
        $payOrder->setAttach($attach);
        $payOrder->setCreateIp($this->requestStack->getCurrentRequest()->getClientIp());

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
        $payOrder->setNotifyUrl($this->urlGenerator->generate('wechat_app_pay_callback', [
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
            'trade_type' => 'APP',
            'nonce_str' => uniqid(),
            'out_trade_no' => $payOrder->getTradeNo(),
            'time_expire' => $payOrder->getExpireTime()->format('YmdHis'),
            'notify_url' => $payOrder->getNotifyUrl(),
            'total_fee' => $payOrder->getTotalFee(),
            'spbill_create_ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
        ];
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

        // {
        //    "appid": "wx218c5a3e352df9e6",
        //    "noncestr": "Sx1Cxe9HSn8cccqG",
        //    "package": "Sign=WXPay",
        //    "partnerid": "1667248468",
        //    "prepayid": "wx12143941790159e2454c637e10644a0001",
        //    "timestamp": 1712903981,
        //    "sign": "A76728B47542AD0D158A891ECFB67296",
        //    "retmsg": "ok"
        // }
        // 创建返回给客户端的数据
        $ret = [];
        $ret['appid'] = $appid;
        $ret['partnerid'] = $json['mch_id'];
        $ret['prepayid'] = $json['prepay_id'];
        $ret['package'] = 'Sign=WXPay';
        $ret['noncestr'] = $json['nonce_str'];
        $ret['timestamp'] = time();
        $stringA = "appid={$ret['appid']}&noncestr={$ret['noncestr']}&package={$ret['package']}&partnerid={$ret['partnerid']}&prepayid={$ret['prepayid']}&timestamp={$ret['timestamp']}";
        $stringSignTemp = "{$stringA}&key={$merchant->getPemKey()}";
        $sign = mb_strtoupper(md5($stringSignTemp));
        $ret['sign'] = $sign;

        $ret['retmsg'] = 'ok';

        return $ret;
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

    public function notify()
    {
    }

    public function getTradeOrderDetail(string $tradeNo): array
    {
        // TODO
        //        $payOrder = $this->payOrderRepository->findOneBy(['tradeNo' => $tradeNo]);
        //        if (!$payOrder) {
        //            throw new \Exception('订单不存在');
        //        }
        //
        //        // 调用远程接口统一下单
        //        $requestJson = [
        //            'appid' => $payOrder->getAppId(),
        //            'mch_id' => $payOrder->getMchId(),
        //            'trade_type' => 'APP',
        //            'nonce_str' => uniqid(),
        //            'out_trade_no' => $payOrder->getTradeNo(),
        //            'sign' => $payOrder->getTradeNo(),
        //        ];
        //
        //        $sign = $this->generateSign($requestJson, $merchant->getPemKey());
        //        $payOrder->setRequestJson(Json::encode($requestJson));
        //        $sign = $this->generateSign($requestJson, $merchant->getPemKey());
        //        $requestJson['sign'] = $sign;
        //        $postXml = XML::build($requestJson);
        //
        //        $response = $this->httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/orderquery', [
        //            'body' => $postXml,
        //        ]);

        return [];
    }
}
