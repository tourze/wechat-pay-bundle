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
        // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
        $platformCertificateFilePath = $merchant->getPemCert();

        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyInstance = Rsa::from($pemKey, Rsa::KEY_TYPE_PRIVATE);
        // 「商户API证书」的「证书序列号」
        $merchantCertificateSerial = $merchant->getCertSerial();
        $platformPublicKeyInstance = $this->getPlatformPublicKey($merchant);
        // 从「微信支付平台证书」中获取「证书序列号」
        $platformCertificateSerial = $this->getPlatformCertificateSerial($merchant);

        // 构造一个 APIv3 客户端实例
        return Builder::factory([
            'mchid' => $merchantId,
            'serial' => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);
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
