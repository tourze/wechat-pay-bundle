# WeChat Pay Bundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/wechat-pay-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/wechat-pay-bundle)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Test Status](https://img.shields.io/badge/tests-84%20passed-brightgreen)](https://github.com/tourze/wechat-pay-bundle)
[![Code Coverage](https://img.shields.io/badge/coverage-95%25-brightgreen)](https://github.com/tourze/wechat-pay-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/wechat-pay-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/wechat-pay-bundle)

[English](README.md) | [中文](README.zh-CN.md)

WeChat Pay integration for Symfony applications, providing payment order management, 
refund processing, and automated bill synchronization.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Console Commands](#console-commands)
- [Entities](#entities)
- [Services](#services)
- [Usage Example](#usage-example)
- [Advanced Usage](#advanced-usage)
- [Development Guide](#development-guide)
- [Dependencies](#dependencies)
- [Contributing](#contributing)
- [License](#license)

## Features

- WeChat Pay order creation and management
- Refund order processing
- Automated bill downloading
- Order status synchronization
- Multi-merchant support
- Payment callback handling

## Installation

```bash
composer require tourze/wechat-pay-bundle
```

## Configuration

Register the bundle in your Symfony application:

```php
// config/bundles.php
return [
    // ...
    WechatPayBundle\WechatPayBundle::class => ['all' => true],
];
```

## Console Commands

### Download Fund Flow Bill
```bash
php bin/console wechat:pay:download-fund-flow-bill
```
Downloads WeChat Pay fund flow bills. Runs automatically at 10:00 and 11:00 daily, 
fetching the last 7 days of fund statements.

### Download Trade Bill
```bash
php bin/console wechat:pay:download-trade-bill
```
Downloads WeChat Pay trade bills. Runs automatically at 10:00 and 11:00 daily, 
fetching the last 7 days of transaction records.

### Check Order Expiration
```bash
php bin/console wechat:pay:check-order-expire
```
Checks and updates expired payment orders. Runs every minute to query orders that 
have expired but are still in INIT status.

### Check Refund Status
```bash
php bin/console wechat:refund:check-order-status
```
Checks and updates refund order status. Runs every minute to query refunds in 
PROCESSING status and update their actual status.

## Entities

- `PayOrder` - Payment order entity with order management
- `RefundOrder` - Refund order entity with goods detail support
- `RefundGoodsDetail` - Refund goods detail entity
- `Merchant` - Merchant configuration entity
- `TradeBill` - Trade bill record entity
- `FundFlowBill` - Fund flow bill record entity

## Enums

- `PayOrderStatus` - Payment order status enumeration
- `AccountType` - WeChat Pay account type enumeration
- `BillType` - Bill type enumeration

## Services

- `UnifiedOrder` - Creates payment orders for various trade types
- `WechatAppPayService` - Handles APP payment order creation
- `WechatJsApiPayService` - Handles JSAPI payment order creation
- `WechatPayBuilder` - Builds WeChat Pay API clients
- `AttributeControllerLoader` - Loads controller attributes for routing

## Usage Example

```php
// Create a payment order
$params = new AppOrderParams();
$params->setMchId('your_merchant_id');
$params->setAppId('your_app_id');
// ... set other parameters
$payOrder = $unifiedOrder->createAppOrder($params);

// Refund processing is handled automatically via RefundOrder entity
$refundOrder = new RefundOrder();
$refundOrder->setPayOrder($payOrder);
$refundOrder->setMoney($amount);
$refundOrder->setReason($reason);
$entityManager->persist($refundOrder);
$entityManager->flush(); // This triggers the refund via RefundOrderListener
```

## Advanced Usage

### Custom Payment Processing

```php
use WechatPayBundle\Service\UnifiedOrder;
use WechatPayBundle\Request\AppOrderParams;

class CustomPaymentService
{
    public function __construct(private UnifiedOrder $unifiedOrder) {}
    
    public function processCustomPayment(array $orderData): array
    {
        $params = new AppOrderParams();
        $params->setMchId($orderData['merchant_id']);
        $params->setAppId($orderData['app_id']);
        $params->setContractId($orderData['order_id']);
        $params->setDescription($orderData['description']);
        $params->setMoney($orderData['amount']);
        
        return $this->unifiedOrder->createAppOrder($params);
    }
}
```

### Handling Payment Callbacks

```php
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;

class PaymentCallbackHandler
{
    public function handleCallback(array $callbackData): void
    {
        $orderId = $callbackData['out_trade_no'];
        $payOrder = $this->findPayOrder($orderId);
        
        if ($callbackData['result_code'] === 'SUCCESS') {
            $payOrder->setStatus(PayOrderStatus::SUCCESS);
            $payOrder->setPayTime(new \DateTime());
        } else {
            $payOrder->setStatus(PayOrderStatus::FAIL);
        }
        
        $this->entityManager->flush();
    }
}
```

### Multi-Merchant Configuration

```php
use WechatPayBundle\Entity\Merchant;

class MerchantService
{
    public function configureMerchant(string $mchId, string $pemKey): Merchant
    {
        $merchant = new Merchant();
        $merchant->setMchId($mchId);
        $merchant->setPemKey($pemKey);
        $merchant->setIsActive(true);
        
        $this->entityManager->persist($merchant);
        $this->entityManager->flush();
        
        return $merchant;
    }
}
```

## Development Guide

### Database Migrations

Use Doctrine migrations to manage database schema:

```bash
php bin/console doctrine:migrations:migrate
```

### Testing

Run unit tests:

```bash
./vendor/bin/phpunit packages/wechat-pay-bundle/tests
```

### Events

This bundle provides the following events:

- `AppPayCallbackSuccessEvent` - Triggered after successful APP payment callback
- `JSAPIPayCallbackSuccessEvent` - Triggered after successful JSAPI payment callback
- `NativePayCallbackSuccessEvent` - Triggered after successful Native payment callback

### Entity Listeners

- `PayOrderListener` - Handles payment order lifecycle events
- `RefundOrderListener` - Handles refund order lifecycle events

## Dependencies

This bundle requires the following packages:

### Core Dependencies
- `php: ^8.1`
- `symfony/framework-bundle: ^7.3`
- `doctrine/orm: ^3.0`
- `wechatpay/wechatpay: ^1.4`
- `nesbot/carbon: ^2.72 || ^3`
- `league/flysystem: ^3.10`
- `yiisoft/json: ^1.0`
- `yiisoft/arrays: ^3`

### Tourze Internal Dependencies
- `tourze/doctrine-snowflake-bundle: 0.1.*`
- `tourze/symfony-snowflake-bundle: 0.0.*`
- `tourze/http-client-bundle: 0.1.*`
- `tourze/symfony-cron-job-bundle: 0.1.*`
- `tourze/doctrine-timestamp-bundle: 0.0.*`
- `tourze/doctrine-track-bundle: 0.1.*`
- `tourze/enum-extra: 0.1.*`
- `tourze/file-storage-bundle: 0.0.*`
- `tourze/xml-helper: 0.0.*`

## Contributing

We welcome Pull Requests and Issues. Please ensure:

1. Follow PSR-12 coding standards
2. Add appropriate unit tests
3. Update relevant documentation

## License

This bundle is part of the Tourze monorepo project.