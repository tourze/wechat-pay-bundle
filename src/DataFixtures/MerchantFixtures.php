<?php

namespace WechatPayBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayBundle\Entity\Merchant;

/**
 * 微信支付商户数据填充
 * 创建测试用的微信支付商户配置数据
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class MerchantFixtures extends Fixture implements FixtureGroupInterface
{
    public const TEST_MERCHANT_REFERENCE = 'test-merchant';
    public const DEMO_MERCHANT_REFERENCE = 'demo-merchant';

    public static function getGroups(): array
    {
        return ['test', 'dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // 创建测试商户
        $testMerchant = new Merchant();
        $testMerchant->setValid(true);
        $testMerchant->setMchId('1234567890');
        $testMerchant->setApiKey('test_api_key_1234567890abcdef1234567890abcdef');
        $testMerchant->setApiKeyV3('test_api_key_v3_1234567890abcdef1234567890');
        $testMerchant->setPemKey('-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7...TEST_KEY...
-----END PRIVATE KEY-----');
        $testMerchant->setCertSerial('1234567890ABCDEF1234567890ABCDEF12345678');
        $testMerchant->setPemCert('-----BEGIN CERTIFICATE-----
MIIDpTCCAo2gAwIBAgIUNzAwRG...TEST_CERT...
-----END CERTIFICATE-----');
        $testMerchant->setRemark('测试商户配置');
        $manager->persist($testMerchant);
        $this->addReference(self::TEST_MERCHANT_REFERENCE, $testMerchant);

        // 创建演示商户
        $demoMerchant = new Merchant();
        $demoMerchant->setValid(false);
        $demoMerchant->setMchId('0987654321');
        $demoMerchant->setApiKey('demo_api_key_abcdef1234567890abcdef1234567890');
        $demoMerchant->setApiKeyV3('demo_api_key_v3_abcdef1234567890abcdef12345');
        $demoMerchant->setPemKey('-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7...DEMO_KEY...
-----END PRIVATE KEY-----');
        $demoMerchant->setCertSerial('ABCDEF1234567890ABCDEF1234567890ABCDEF12');
        $demoMerchant->setPemCert('-----BEGIN CERTIFICATE-----
MIIDpTCCAo2gAwIBAgIUNzAwRG...DEMO_CERT...
-----END CERTIFICATE-----');
        $demoMerchant->setRemark('演示商户配置（已禁用）');
        $manager->persist($demoMerchant);
        $this->addReference(self::DEMO_MERCHANT_REFERENCE, $demoMerchant);

        $manager->flush();
    }
}
