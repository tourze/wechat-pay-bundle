<?php

namespace WechatPayBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WechatPayBundle\Request\AppOrderParams;

/**
 * @internal
 */
#[CoversClass(AppOrderParams::class)]
final class AppOrderParamsTest extends TestCase
{
    private AppOrderParams $params;

    protected function setUp(): void
    {
        parent::setUp();

        $this->params = new AppOrderParams();
    }

    /**
     * 测试商户ID的getter和setter
     */
    public function testMchId(): void
    {
        $this->params->setMchId('test_mch_id');
        $this->assertEquals('test_mch_id', $this->params->getMchId());
    }

    /**
     * 测试应用ID的getter和setter
     */
    public function testAppId(): void
    {
        $this->params->setAppId('wx1234567890');
        $this->assertEquals('wx1234567890', $this->params->getAppId());
    }

    /**
     * 测试订单ID的getter和setter
     */
    public function testContractId(): void
    {
        $this->params->setContractId('order_123456');
        $this->assertEquals('order_123456', $this->params->getContractId());
    }

    /**
     * 测试币种的getter和setter
     */
    public function testCurrency(): void
    {
        // 测试默认值
        $this->assertEquals('CNY', $this->params->getCurrency());

        // 测试设置新值
        $this->params->setCurrency('USD');
        $this->assertEquals('USD', $this->params->getCurrency());
    }

    /**
     * 测试金额的getter和setter
     */
    public function testMoney(): void
    {
        $this->params->setMoney(100);
        $this->assertEquals(100, $this->params->getMoney());
    }

    /**
     * 测试描述的getter和setter
     */
    public function testDescription(): void
    {
        // 测试默认值
        $this->assertEquals('新订单', $this->params->getDescription());

        // 测试设置新值
        $this->params->setDescription('测试订单');
        $this->assertEquals('测试订单', $this->params->getDescription());
    }

    /**
     * 测试附加信息的getter和setter
     */
    public function testAttach(): void
    {
        // 测试默认值
        $this->assertEquals('', $this->params->getAttach());

        // 测试设置新值
        $this->params->setAttach('附加信息');
        $this->assertEquals('附加信息', $this->params->getAttach());
    }

    /**
     * 测试openId的getter和setter
     */
    public function testOpenId(): void
    {
        // 测试默认值
        $this->assertEquals('', $this->params->getOpenId());

        // 测试设置新值
        $this->params->setOpenId('user_open_id');
        $this->assertEquals('user_open_id', $this->params->getOpenId());
    }

    /**
     * 测试完整参数设置
     */
    public function testFullParameterSetting(): void
    {
        $this->params->setMchId('test_mch_id');
        $this->params->setAppId('wx1234567890');
        $this->params->setContractId('order_123456');
        $this->params->setCurrency('CNY');
        $this->params->setMoney(100);
        $this->params->setDescription('测试订单');
        $this->params->setAttach('附加信息');
        $this->params->setOpenId('user_open_id');

        $this->assertEquals('test_mch_id', $this->params->getMchId());
        $this->assertEquals('wx1234567890', $this->params->getAppId());
        $this->assertEquals('order_123456', $this->params->getContractId());
        $this->assertEquals('CNY', $this->params->getCurrency());
        $this->assertEquals(100, $this->params->getMoney());
        $this->assertEquals('测试订单', $this->params->getDescription());
        $this->assertEquals('附加信息', $this->params->getAttach());
        $this->assertEquals('user_open_id', $this->params->getOpenId());
    }

    /**
     * 测试设置非默认币种
     */
    public function testNonDefaultCurrency(): void
    {
        $this->params->setCurrency('USD');
        $this->assertEquals('USD', $this->params->getCurrency());
    }

    /**
     * 测试设置不同金额
     */
    public function testDifferentMoneyValues(): void
    {
        // 测试零值
        $this->params->setMoney(0);
        $this->assertEquals(0, $this->params->getMoney());

        // 测试小额
        $this->params->setMoney(1);
        $this->assertEquals(1, $this->params->getMoney());

        // 测试大额
        $this->params->setMoney(1000000);
        $this->assertEquals(1000000, $this->params->getMoney());
    }
}
