<?php

namespace WechatPayBundle\Service;

use WeChatPay\Builder;
use WeChatPay\BuilderChainable;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use WechatPayBundle\Entity\Merchant;

class WechatPayBuilder
{
    public function genBuilder(Merchant $merchant): BuilderChainable
    {
        $merchantId = $merchant->getMchId();

        $pemKey = $merchant->getPemKey();

        // 从本地文件中加载「商户API私钥」，用于生成请求的签名
        $merchantPrivateKeyFilePath = $pemKey;
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);

        // 「商户API证书」的「证书序列号」
        $merchantCertificateSerial = $merchant->getCertSerial();

        // 从本地文件中加载「微信支付公钥」，用来验证微信支付应答的签名
        $platformPublicKeyFilePath = $merchant->getPublicKey();
        $platformPublicKeyInstance = Rsa::from($platformPublicKeyFilePath, Rsa::KEY_TYPE_PUBLIC);

        // 「微信支付公钥」的「微信支付公钥ID」
        // 需要在 商户平台 -> 账户中心 -> API安全 查询
        $platformPublicKeyId = $merchant->getPublicKeyId();

        // 构造一个 APIv3 客户端实例(微信支付公钥模式)
        $instance = Builder::factory([
            'mchid'      => $merchantId,
            'serial'     => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs'      => [
                $platformPublicKeyId => $platformPublicKeyInstance,
            ]
        ]);

        return $instance;
    }

    /**
     * @return \OpenSSLAsymmetricKey|\OpenSSLCertificate|resource|mixed
     */
    public function getPlatformPublicKey(Merchant $merchant)
    {
        $certificate = $merchant->getPemCert();
        if (null === $certificate || '' === $certificate) {
            throw new \RuntimeException('未配置微信支付平台证书');
        }

        return Rsa::from($certificate, Rsa::KEY_TYPE_PUBLIC);
    }

    public function getPlatformCertificateSerial(Merchant $merchant): string
    {
        $certificate = $merchant->getPemCert();
        if (null === $certificate || '' === $certificate) {
            throw new \RuntimeException('未配置微信支付平台证书');
        }

        return PemUtil::parseCertificateSerialNo($certificate);
    }
}
