<?php

namespace WechatPayBundle\Tests\Service;

use BaconQrCodeBundle\Service\QrcodeService;
use Doctrine\ORM\EntityManagerInterface;
use HttpClientBundle\Service\SmartHttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;
use WechatPayBundle\Repository\MerchantRepository;
use WechatPayBundle\Request\AppOrderParams;
use WechatPayBundle\Service\UnifiedOrder;

class UnifiedOrderTest extends TestCase
{
    private UnifiedOrder $unifiedOrder;
    private MockObject $merchantRepository;
    private MockObject $logger;
    private MockObject $httpClient;
    private MockObject $urlGenerator;
    private MockObject $requestStack;
    private MockObject $qrcodeService;
    private MockObject $entityManager;
    private Request $request;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->merchantRepository = $this->createMock(MerchantRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(SmartHttpClient::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->qrcodeService = $this->createMock(QrcodeService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->request = $this->createMock(Request::class);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
        
        $this->unifiedOrder = new class(
            $this->merchantRepository,
            $this->logger,
            $this->httpClient,
            $this->urlGenerator,
            $this->requestStack,
            $this->qrcodeService,
            $this->entityManager
        ) extends UnifiedOrder {
            // 使用匿名类扩展 UnifiedOrder，允许我们设置 protected 的 tradeType 属性
            public function setTradeType(string $tradeType): void
            {
                $this->tradeType = $tradeType;
            }
        };
        
        // 设置默认的 tradeType
        $this->unifiedOrder->setTradeType('APP');
    }
    
    /**
     * 测试 createH5Order 基本流程
     */
    public function testCreateH5Order_basicFlow(): void
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
        
        // Mock 二维码服务
        $this->qrcodeService->method('getImageUrl')
            ->willReturn('https://example.com/qrcode/image.png');
        
        // Mock HTTP响应返回内容
        $httpResponse = $this->createMock(ResponseInterface::class);
        $httpResponse->method('getContent')
            ->willReturn('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg><prepay_id><![CDATA[wx123456789]]></prepay_id><code_url><![CDATA[weixin://wxpay/s/An4baqw]]></code_url><mch_id><![CDATA[1234567890]]></mch_id><nonce_str><![CDATA[abcdef123456]]></nonce_str></xml>');
        
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
        $result = $this->unifiedOrder->createH5Order($params);
        
        // 验证结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code_url', $result);
        $this->assertArrayHasKey('timeStamp', $result);
        $this->assertArrayHasKey('nonceStr', $result);
        $this->assertArrayHasKey('package', $result);
        $this->assertArrayHasKey('signType', $result);
        $this->assertEquals('https://example.com/qrcode/image.png', $result['code_url']);
        $this->assertEquals('abcdef123456', $result['nonceStr']);
        $this->assertEquals('prepay_id=wx123456789', $result['package']);
        $this->assertEquals('MD5', $result['signType']);
    }
    
    /**
     * 测试未设置 tradeType 时抛出异常
     */
    public function testCreateH5Order_throwsExceptionWhenNoTradeType(): void
    {
        // 设置 tradeType 为空
        $this->unifiedOrder->setTradeType('');
        
        // 准备参数
        $params = new AppOrderParams();
        $params->setMchId('1234567890');
        $params->setAppId('wxAppId12345');
        $params->setContractId('order_123456');
        $params->setDescription('测试订单');
        $params->setMoney(100);
        
        // 验证抛出异常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('请设置下单类型');
        
        $this->unifiedOrder->createH5Order($params);
    }
    
    /**
     * 测试 createAppOrder 基本流程
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
        $httpResponse = $this->createMock(ResponseInterface::class);
        $httpResponse->method('getContent')
            ->willReturn('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg><prepay_id><![CDATA[wx123456789]]></prepay_id><mch_id><![CDATA[1234567890]]></mch_id><nonce_str><![CDATA[abcdef123456]]></nonce_str></xml>');
        
        $this->httpClient->method('request')
            ->willReturn($httpResponse);
        
        // 执行测试
        $result = $this->unifiedOrder->createAppOrder($params);
        
        // 验证结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('appId', $result);
        $this->assertArrayHasKey('timeStamp', $result);
        $this->assertArrayHasKey('nonceStr', $result);
        $this->assertArrayHasKey('package', $result);
        $this->assertArrayHasKey('signType', $result);
        $this->assertArrayHasKey('paySign', $result);
        $this->assertEquals('wxAppId12345', $result['appId']);
        $this->assertEquals('abcdef123456', $result['nonceStr']);
        $this->assertEquals('prepay_id=wx123456789', $result['package']);
        $this->assertEquals('MD5', $result['signType']);
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
        $sign = $this->unifiedOrder->generateSign($attributes, $key);
        
        // 验证
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
        $httpResponse = $this->createMock(ResponseInterface::class);
        $httpResponse->method('getContent')
            ->willReturn('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[参数错误]]></return_msg></xml>');
        
        $this->httpClient->method('request')
            ->willReturn($httpResponse);
        
        // 验证抛出异常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('获取微信APP支付关键参数出错');
        
        $this->unifiedOrder->createAppOrder($params);
    }
    
    /**
     * 测试不指定商户ID时自动选择默认商户
     */
    public function testCreateAppOrder_defaultMerchant(): void
    {
        // 准备商户数据
        $merchant = new Merchant();
        $merchant->setMchId('default_merchant');
        $merchant->setPemKey('test_key');
        
        // 准备参数 - 不设置商户ID
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
        
        // Mock 其他依赖
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');
        $this->request->method('getClientIp')->willReturn('127.0.0.1');
        
        $httpResponse = $this->createMock(ResponseInterface::class);
        $httpResponse->method('getContent')
            ->willReturn('<xml><return_code><![CDATA[SUCCESS]]></return_code><prepay_id><![CDATA[wx123456789]]></prepay_id><mch_id><![CDATA[default_merchant]]></mch_id><nonce_str><![CDATA[abcdef123456]]></nonce_str></xml>');
        
        $this->httpClient->method('request')->willReturn($httpResponse);
        
        // 执行测试
        $result = $this->unifiedOrder->createAppOrder($params);
        
        // 验证调用了正确的商户查询方法
        $this->assertIsArray($result);
    }
    
    /**
     * 测试附加信息的传递
     */
    public function testCreateAppOrder_withAttach(): void
    {
        // 准备商户数据
        $merchant = new Merchant();
        $merchant->setMchId('1234567890');
        $merchant->setPemKey('test_key');
        
        // 准备参数 - 设置附加信息
        $params = new AppOrderParams();
        $params->setMchId('1234567890');
        $params->setAppId('wxAppId12345');
        $params->setContractId('order_123456');
        $params->setDescription('测试订单');
        $params->setMoney(100);
        $params->setAttach('附加信息');
        
        // Mock 依赖
        $this->merchantRepository->method('findOneBy')->willReturn($merchant);
        $this->urlGenerator->method('generate')->willReturn('https://example.com/callback');
        $this->request->method('getClientIp')->willReturn('127.0.0.1');
        
        // 验证 HTTP 请求体中包含附加信息
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('https://api.mch.weixin.qq.com/pay/unifiedorder'),
                $this->callback(function ($options) {
                    if (!isset($options['body'])) {
                        return false;
                    }
                    // 验证XML中包含附加信息
                    return strpos($options['body'], '<attach>') !== false;
                })
            )
            ->willReturn($this->createConfiguredMock(ResponseInterface::class, [
                'getContent' => '<xml><return_code><![CDATA[SUCCESS]]></return_code><prepay_id><![CDATA[wx123456789]]></prepay_id><mch_id><![CDATA[1234567890]]></mch_id><nonce_str><![CDATA[abcdef123456]]></nonce_str></xml>',
            ]));
        
        // 执行测试并添加断言，以避免测试被标记为risky
        $result = $this->unifiedOrder->createAppOrder($params);
        $this->assertIsArray($result);
    }
} 