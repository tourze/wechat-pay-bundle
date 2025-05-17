<?php

namespace WechatPayBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use HttpClientBundle\Service\SmartHttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Repository\PayOrderRepository;
use WechatPayBundle\Request\AppOrderParams;
use WechatPayBundle\Service\WechatAppPayService;

class WechatAppPayServiceTest extends TestCase
{
    private WechatAppPayService $service;
    private MockObject $merchantRepository;
    private MockObject $logger;
    private MockObject $httpClient;
    private MockObject $urlGenerator;
    private MockObject $payOrderRepository;
    private MockObject $requestStack;
    private MockObject $entityManager;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->merchantRepository = $this->createMock(MerchantRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(SmartHttpClient::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->payOrderRepository = $this->createMock(PayOrderRepository::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->request = $this->createMock(Request::class);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
        
        $this->service = new WechatAppPayService(
            $this->merchantRepository,
            $this->logger,
            $this->httpClient,
            $this->urlGenerator,
            $this->payOrderRepository,
            $this->requestStack,
            $this->entityManager
        );
    }

    /**
     * 测试创建应用订单基本流程
     */
    public function testCreateAppOrder_basicFlow(): void
    {
        // 准备商户数据
        $merchant = new Merchant();
        $merchant->setMchId('1234567890');
        $merchant->setPemKey('test_key');
        
        // 准备参数
        $params = new AppOrderParams();
        $params->setMchId('1234567890');
        $params->setAppId('wxAppId12345');
        $params->setContractId('order_123456');
        $params->setDescription('测试订单');
        $params->setMoney(100); // 1元
        
        // Mock MerchantRepository 返回
        $this->merchantRepository->method('findOneBy')
            ->willReturn($merchant);
        
        // Mock URL生成器返回
        $this->urlGenerator->method('generate')
            ->willReturn('https://example.com/callback');
        
        // Mock 客户端IP
        $this->request->method('getClientIp')
            ->willReturn('127.0.0.1');
        
        // Mock HTTP响应返回内容
        $httpResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $httpResponse->method('getContent')
            ->willReturn('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg><prepay_id><![CDATA[wx123456789]]></prepay_id><mch_id><![CDATA[1234567890]]></mch_id><nonce_str><![CDATA[abcdef123456]]></nonce_str></xml>');
        
        $this->httpClient->method('request')
            ->willReturn($httpResponse);
        
        // 由于我们需要测试实体持久化，但不想实际写入数据库
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($payOrder) {
                $this->assertInstanceOf(PayOrder::class, $payOrder);
                $this->assertEquals(PayOrderStatus::INIT, $payOrder->getStatus());
                $this->assertEquals('测试订单', $payOrder->getBody());
                $this->assertEquals('wxAppId12345', $payOrder->getAppId());
                $this->assertEquals('APP', $payOrder->getTradeType());
                return true;
            }));
        
        // 执行测试
        $result = $this->service->createAppOrder($params);
        
        // 验证结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('appid', $result);
        $this->assertArrayHasKey('partnerid', $result);
        $this->assertArrayHasKey('prepayid', $result);
        $this->assertArrayHasKey('package', $result);
        $this->assertArrayHasKey('noncestr', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('sign', $result);
        $this->assertEquals('wxAppId12345', $result['appid']);
        $this->assertEquals('1234567890', $result['partnerid']);
        $this->assertEquals('wx123456789', $result['prepayid']);
        $this->assertEquals('Sign=WXPay', $result['package']);
        $this->assertEquals('abcdef123456', $result['noncestr']);
        $this->assertIsNumeric($result['timestamp']);
    }

    /**
     * 测试未指定商户ID时自动选择第一个商户
     */
    public function testCreateAppOrder_withoutMerchantId(): void
    {
        // 准备商户数据
        $merchant = new Merchant();
        $merchant->setMchId('default_merchant');
        $merchant->setPemKey('test_key');
        
        // 准备参数，不设置商户ID
        $params = new AppOrderParams();
        $params->setAppId('wxAppId12345');
        $params->setContractId('order_123456');
        $params->setDescription('测试订单');
        $params->setMoney(100);
        
        // Mock MerchantRepository 返回
        $this->merchantRepository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['id' => 'DESC'])
            ->willReturn($merchant);
        
        // Mock URL生成器返回
        $this->urlGenerator->method('generate')
            ->willReturn('https://example.com/callback');
        
        // Mock 客户端IP
        $this->request->method('getClientIp')
            ->willReturn('127.0.0.1');
        
        // Mock HTTP响应返回
        $httpResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $httpResponse->method('getContent')
            ->willReturn('<xml><return_code><![CDATA[SUCCESS]]></return_code><prepay_id><![CDATA[wx123456789]]></prepay_id><mch_id><![CDATA[default_merchant]]></mch_id><nonce_str><![CDATA[abcdef123456]]></nonce_str></xml>');
        
        $this->httpClient->method('request')
            ->willReturn($httpResponse);
        
        // 执行测试
        $result = $this->service->createAppOrder($params);
        
        // 验证结果
        $this->assertIsArray($result);
        $this->assertEquals('default_merchant', $result['partnerid']);
    }

    /**
     * 测试生成签名
     */
    public function testGenerateSign(): void
    {
        $attributes = [
            'appid' => 'wx123456',
            'mch_id' => '1234567890',
            'nonce_str' => 'abc123',
            'body' => '测试商品',
            'out_trade_no' => 'order123456',
            'total_fee' => 100,
            'spbill_create_ip' => '127.0.0.1',
            'notify_url' => 'https://example.com/notify',
            'trade_type' => 'APP',
        ];
        
        $key = 'test_key_123456';
        
        // 计算预期签名
        $expectedSignData = $attributes;
        ksort($expectedSignData);
        $expectedSignData['key'] = $key;
        $expectedSign = strtoupper(md5(urldecode(http_build_query($expectedSignData))));
        
        // 实际生成签名
        $sign = $this->service->generateSign($attributes, $key);
        
        // 验证
        $this->assertEquals($expectedSign, $sign);
    }

    /**
     * 测试获取订单详情
     */
    public function testGetTradeOrderDetail(): void
    {
        // 由于方法未实现，仅测试返回空数组
        $result = $this->service->getTradeOrderDetail('order123456');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    /**
     * 测试通知方法
     */
    public function testNotify(): void
    {
        // 由于方法暂未实现，只测试方法存在
        $this->service->notify();
        $this->assertTrue(true); // 表示测试通过
    }
    
    /**
     * 测试签名生成输入参数验证
     */
    public function testGenerateSign_withDifferentEncryptMethod(): void
    {
        $attributes = [
            'appid' => 'wx123456',
            'mch_id' => '1234567890',
        ];
        
        $key = 'test_key_123456';
        
        // 使用sha1而不是默认的md5
        $expectedSignData = $attributes;
        ksort($expectedSignData);
        $expectedSignData['key'] = $key;
        $expectedSign = strtoupper(sha1(urldecode(http_build_query($expectedSignData))));
        
        $sign = $this->service->generateSign($attributes, $key, 'sha1');
        
        $this->assertEquals($expectedSign, $sign);
    }

    /**
     * 测试当微信接口没有返回prepay_id时抛出异常
     */
    public function testCreateAppOrder_throwsExceptionWhenNoPrepayId(): void
    {
        // 准备商户数据
        $merchant = new Merchant();
        $merchant->setMchId('1234567890');
        $merchant->setPemKey('test_key');
        
        // 准备参数
        $params = new AppOrderParams();
        $params->setMchId('1234567890');
        $params->setAppId('wxAppId12345');
        $params->setContractId('order_123456');
        $params->setDescription('测试订单');
        $params->setMoney(100);
        
        // Mock MerchantRepository 返回
        $this->merchantRepository->method('findOneBy')
            ->willReturn($merchant);
        
        // Mock URL生成器返回
        $this->urlGenerator->method('generate')
            ->willReturn('https://example.com/callback');
        
        // Mock 客户端IP
        $this->request->method('getClientIp')
            ->willReturn('127.0.0.1');
        
        // Mock HTTP响应返回内容 - 没有prepay_id
        $httpResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $httpResponse->method('getContent')
            ->willReturn('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[参数错误]]></return_msg></xml>');
        
        $this->httpClient->method('request')
            ->willReturn($httpResponse);
        
        // 验证抛出异常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('获取微信APP支付关键参数出错');
        
        $this->service->createAppOrder($params);
    }
} 