# 微信支付 Bundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/wechat-pay-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/wechat-pay-bundle)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Test Status](https://img.shields.io/badge/tests-84%20passed-brightgreen)](https://github.com/tourze/wechat-pay-bundle)
[![Code Coverage](https://img.shields.io/badge/coverage-95%25-brightgreen)](https://github.com/tourze/wechat-pay-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/wechat-pay-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/wechat-pay-bundle)

[English](README.md) | [中文](README.zh-CN.md)

为 Symfony 应用提供微信支付集成，包括支付订单管理、退款处理和自动账单同步功能。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [配置](#配置)
- [控制台命令](#控制台命令)
- [实体](#实体)
- [服务](#服务)
- [使用示例](#使用示例)
- [高级用法](#高级用法)
- [开发指南](#开发指南)
- [依赖要求](#依赖要求)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- 微信支付订单创建与管理
- 退款订单处理
- 自动下载账单
- 订单状态同步
- 多商户支持
- 支付回调处理

## 安装

```bash
composer require tourze/wechat-pay-bundle
```

## 配置

在 Symfony 应用中注册此 Bundle：

```php
// config/bundles.php
return [
    // ...
    WechatPayBundle\WechatPayBundle::class => ['all' => true],
];
```

## 控制台命令

### 下载资金流水账单
```bash
php bin/console wechat:pay:download-fund-flow-bill
```
下载微信支付资金流水账单。每天 10:00 和 11:00 自动执行，获取最近 7 天的资金流水。

### 下载交易账单
```bash
php bin/console wechat:pay:download-trade-bill
```
下载微信支付交易账单。每天 10:00 和 11:00 自动执行，获取最近 7 天的交易记录。

### 检查订单过期状态
```bash
php bin/console wechat:pay:check-order-expire
```
检查并更新过期的支付订单。每分钟执行一次，查询已过期但仍处于 INIT 状态的订单。

### 检查退款状态
```bash
php bin/console wechat:refund:check-order-status
```
检查并更新退款订单状态。每分钟执行一次，查询处于 PROCESSING 状态的退款并更新其实际状态。

## 实体

- `PayOrder` - 支付订单实体，提供订单管理
- `RefundOrder` - 退款订单实体，支持商品详情
- `RefundGoodsDetail` - 退款商品详情实体
- `Merchant` - 商户配置实体
- `TradeBill` - 交易账单记录实体
- `FundFlowBill` - 资金流水账单记录实体

## 枚举

- `PayOrderStatus` - 支付订单状态枚举
- `AccountType` - 微信支付账户类型枚举
- `BillType` - 账单类型枚举

## 服务

- `UnifiedOrder` - 创建各种交易类型的支付订单
- `WechatAppPayService` - 处理 APP 支付订单创建
- `WechatJsApiPayService` - 处理 JSAPI 支付订单创建
- `WechatPayBuilder` - 构建微信支付 API 客户端
- `AttributeControllerLoader` - 加载控制器属性用于路由

## 使用示例

```php
// 创建支付订单
$params = new AppOrderParams();
$params->setMchId('your_merchant_id');
$params->setAppId('your_app_id');
// ... 设置其他参数
$payOrder = $unifiedOrder->createAppOrder($params);

// 退款处理通过 RefundOrder 实体自动处理
$refundOrder = new RefundOrder();
$refundOrder->setPayOrder($payOrder);
$refundOrder->setMoney($amount);
$refundOrder->setReason($reason);
$entityManager->persist($refundOrder);
$entityManager->flush(); // 这会通过 RefundOrderListener 触发退款
```

## 高级用法

### 自定义支付处理

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

### 处理支付回调

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

### 多商户配置

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

## 开发指南

### 数据库迁移

使用 Doctrine 迁移管理数据库结构：

```bash
php bin/console doctrine:migrations:migrate
```

### 测试

运行单元测试：

```bash
./vendor/bin/phpunit packages/wechat-pay-bundle/tests
```

### 事件

本 Bundle 提供以下事件：

- `AppPayCallbackSuccessEvent` - APP 支付回调成功后触发
- `JSAPIPayCallbackSuccessEvent` - JSAPI 支付回调成功后触发
- `NativePayCallbackSuccessEvent` - Native 支付回调成功后触发

### 实体监听器

- `PayOrderListener` - 处理支付订单生命周期事件
- `RefundOrderListener` - 处理退款订单生命周期事件

## 依赖要求

此 Bundle 需要以下依赖包：

### 核心依赖
- `php: ^8.1`
- `symfony/framework-bundle: ^7.3`
- `doctrine/orm: ^3.0`
- `wechatpay/wechatpay: ^1.4`
- `nesbot/carbon: ^2.72 || ^3`
- `league/flysystem: ^3.10`
- `yiisoft/json: ^1.0`
- `yiisoft/arrays: ^3`

### Tourze 内部依赖
- `tourze/doctrine-snowflake-bundle: 0.1.*`
- `tourze/symfony-snowflake-bundle: 0.0.*`
- `tourze/http-client-bundle: 0.1.*`
- `tourze/symfony-cron-job-bundle: 0.1.*`
- `tourze/doctrine-timestamp-bundle: 0.0.*`
- `tourze/doctrine-track-bundle: 0.1.*`
- `tourze/enum-extra: 0.1.*`
- `tourze/file-storage-bundle: 0.0.*`
- `tourze/xml-helper: 0.0.*`

## 贡献

欢迎提交 Pull Request 和 Issue。请确保：

1. 遵循 PSR-12 编码规范
2. 添加适当的单元测试
3. 更新相关文档

## 许可证

此 Bundle 是 Tourze monorepo 项目的一部分。