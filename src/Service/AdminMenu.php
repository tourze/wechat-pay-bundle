<?php

namespace WechatPayBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use WechatPayBundle\Entity\FundFlowBill;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Entity\RefundGoodsDetail;
use WechatPayBundle\Entity\RefundOrder;
use WechatPayBundle\Entity\TradeBill;

/**
 * 微信支付管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('微信支付')) {
            $item->addChild('微信支付');
        }

        $wechatPayMenu = $item->getChild('微信支付');
        if (null === $wechatPayMenu) {
            return;
        }

        // 商户管理菜单
        $wechatPayMenu->addChild('商户管理')
            ->setUri($this->linkGenerator->getCurdListPage(Merchant::class))
            ->setAttribute('icon', 'fas fa-store')
        ;

        // 支付订单菜单
        $wechatPayMenu->addChild('支付订单')
            ->setUri($this->linkGenerator->getCurdListPage(PayOrder::class))
            ->setAttribute('icon', 'fas fa-credit-card')
        ;

        // 退款订单菜单
        $wechatPayMenu->addChild('退款订单')
            ->setUri($this->linkGenerator->getCurdListPage(RefundOrder::class))
            ->setAttribute('icon', 'fas fa-undo')
        ;

        // 退款商品明细菜单
        $wechatPayMenu->addChild('退款商品明细')
            ->setUri($this->linkGenerator->getCurdListPage(RefundGoodsDetail::class))
            ->setAttribute('icon', 'fas fa-list-ul')
        ;

        // 资金账单菜单
        $wechatPayMenu->addChild('资金账单')
            ->setUri($this->linkGenerator->getCurdListPage(FundFlowBill::class))
            ->setAttribute('icon', 'fas fa-money-bill-wave')
        ;

        // 交易账单菜单
        $wechatPayMenu->addChild('交易账单')
            ->setUri($this->linkGenerator->getCurdListPage(TradeBill::class))
            ->setAttribute('icon', 'fas fa-file-invoice-dollar')
        ;
    }
}
