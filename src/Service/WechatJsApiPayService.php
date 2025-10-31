<?php

namespace WechatPayBundle\Service;

use BaconQrCodeBundle\Service\QrcodeService;
use Doctrine\ORM\EntityManagerInterface;
use HttpClientBundle\Service\SmartHttpClient;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WechatPayBundle\Repository\MerchantRepository;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'wechat_pay')]
class WechatJsApiPayService extends UnifiedOrder
{
    public function __construct(
        MerchantRepository $merchantRepository,
        LoggerInterface $logger,
        SmartHttpClient $httpClient,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        QrcodeService $qrcodeService,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($merchantRepository, $logger, $httpClient, $urlGenerator, $requestStack, $qrcodeService, $entityManager);
        $this->tradeType = 'JSAPI';
    }
}
