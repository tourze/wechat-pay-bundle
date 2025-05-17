<?php

namespace WechatPayBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use WechatPayBundle\Entity\Merchant;

class MerchantTest extends TestCase
{
    private Merchant $merchant;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = new Merchant();
    }
    
    /**
     * 测试设置和获取商户号
     */
    public function testMchId(): void
    {
        $this->merchant->setMchId('1234567890');
        $this->assertEquals('1234567890', $this->merchant->getMchId());
    }
    
    /**
     * 测试设置和获取API密钥
     */
    public function testApiKey(): void
    {
        $this->merchant->setApiKey('test_api_key');
        $this->assertEquals('test_api_key', $this->merchant->getApiKey());
    }
    
    /**
     * 测试设置和获取商户API私钥
     */
    public function testPemKey(): void
    {
        $pemKey = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSj\n-----END PRIVATE KEY-----";
        $this->merchant->setPemKey($pemKey);
        $this->assertEquals($pemKey, $this->merchant->getPemKey());
    }
    
    /**
     * 测试设置和获取证书序列号
     */
    public function testCertSerial(): void
    {
        $this->merchant->setCertSerial('certificate-serial-number');
        $this->assertEquals('certificate-serial-number', $this->merchant->getCertSerial());
    }
    
    /**
     * 测试设置和获取微信支付平台证书
     */
    public function testPemCert(): void
    {
        $pemCert = "-----BEGIN CERTIFICATE-----\nMIID8zCCAtugAwIBAgIUMFo9H8B+QxF\n-----END CERTIFICATE-----";
        $this->merchant->setPemCert($pemCert);
        $this->assertEquals($pemCert, $this->merchant->getPemCert());
    }
    
    /**
     * 测试设置和获取备注
     */
    public function testRemark(): void
    {
        $this->merchant->setRemark('测试备注');
        $this->assertEquals('测试备注', $this->merchant->getRemark());
    }
    
    /**
     * 测试设置和获取有效状态
     */
    public function testValid(): void
    {
        // 默认应该是 false
        $this->assertFalse($this->merchant->isValid());
        
        // 设置为有效
        $this->merchant->setValid(true);
        $this->assertTrue($this->merchant->isValid());
        
        // 设置为无效
        $this->merchant->setValid(false);
        $this->assertFalse($this->merchant->isValid());
    }
    
    /**
     * 测试设置和获取创建时间
     */
    public function testCreateTime(): void
    {
        $now = new \DateTime();
        $this->merchant->setCreateTime($now);
        $this->assertSame($now, $this->merchant->getCreateTime());
    }
    
    /**
     * 测试设置和获取更新时间
     */
    public function testUpdateTime(): void
    {
        $now = new \DateTime();
        $this->merchant->setUpdateTime($now);
        $this->assertSame($now, $this->merchant->getUpdateTime());
    }
    
    /**
     * 测试设置和获取创建人
     */
    public function testCreatedBy(): void
    {
        $this->merchant->setCreatedBy('user1');
        $this->assertEquals('user1', $this->merchant->getCreatedBy());
    }
    
    /**
     * 测试设置和获取更新人
     */
    public function testUpdatedBy(): void
    {
        $this->merchant->setUpdatedBy('user2');
        $this->assertEquals('user2', $this->merchant->getUpdatedBy());
    }
    
    /**
     * 测试字符串表示
     */
    public function testToString(): void
    {
        // 无ID时返回空字符串
        $this->assertEquals('', (string)$this->merchant);
        
        // 使用反射设置ID
        $reflection = new \ReflectionClass(Merchant::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this->merchant, '12345');
        
        // 设置商户号
        $this->merchant->setMchId('merchant_id_12345');
        
        // 测试字符串转换
        $this->assertEquals('merchant_id_12345', (string)$this->merchant);
    }
    
    /**
     * 测试链式调用
     */
    public function testFluentInterface(): void
    {
        $result = $this->merchant
            ->setMchId('1234567890')
            ->setApiKey('api_key')
            ->setPemKey('pem_key')
            ->setCertSerial('cert_serial')
            ->setPemCert('pem_cert')
            ->setRemark('remark')
            ->setValid(true)
            ->setCreatedBy('user1')
            ->setUpdatedBy('user2');
        
        $this->assertSame($this->merchant, $result);
        $this->assertEquals('1234567890', $this->merchant->getMchId());
        $this->assertEquals('api_key', $this->merchant->getApiKey());
        $this->assertEquals('pem_key', $this->merchant->getPemKey());
        $this->assertEquals('cert_serial', $this->merchant->getCertSerial());
        $this->assertEquals('pem_cert', $this->merchant->getPemCert());
        $this->assertEquals('remark', $this->merchant->getRemark());
        $this->assertTrue($this->merchant->isValid());
        $this->assertEquals('user1', $this->merchant->getCreatedBy());
        $this->assertEquals('user2', $this->merchant->getUpdatedBy());
    }
} 