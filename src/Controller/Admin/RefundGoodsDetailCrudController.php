<?php

namespace WechatPayBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use WechatPayBundle\Entity\RefundGoodsDetail;
use WechatPayBundle\Entity\RefundOrder;

/**
 * @extends AbstractCrudController<RefundGoodsDetail>
 */
#[AdminCrud(routePath: '/wechat-pay/refund-goods-detail', routeName: 'wechat_pay_refund_goods_detail')]
final class RefundGoodsDetailCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RefundGoodsDetail::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('退款商品明细')
            ->setEntityLabelInPlural('退款商品明细管理')
            ->setPageTitle('index', '退款商品明细列表')
            ->setPageTitle('detail', '商品明细详情')
            ->setPageTitle('new', '创建商品明细')
            ->setPageTitle('edit', '编辑商品明细')
            ->setHelp('index', '管理退款订单中的商品明细信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['merchantGoodsId', 'wechatpayGoodsId', 'goodsName'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->hideOnForm();

        yield AssociationField::new('refundOrder', '退款订单')
            ->setRequired(true)
            ->setHelp('选择对应的退款订单')
        ;

        yield TextField::new('merchantGoodsId', '商户商品编码')
            ->setRequired(true)
            ->setHelp('商户侧商品编码')
        ;

        yield TextField::new('wechatpayGoodsId', '微信商品编码')
            ->setHelp('微信支付商品编码')
            ->hideOnIndex()
        ;

        yield TextField::new('goodsName', '商品名称')
            ->setHelp('商品名称')
        ;

        yield IntegerField::new('unitPrice', '单价（分）')
            ->setRequired(true)
            ->setHelp('商品单价，单位为分')
            ->formatValue(function (mixed $value): string {
                if (is_int($value) && $value > 0) {
                    return number_format($value / 100, 2) . ' 元';
                }

                return '0.00 元';
            })
        ;

        yield IntegerField::new('refundAmount', '退款金额（分）')
            ->setRequired(true)
            ->setHelp('商品退款金额，单位为分')
            ->formatValue(function (mixed $value): string {
                if (is_int($value) && $value > 0) {
                    return number_format($value / 100, 2) . ' 元';
                }

                return '0.00 元';
            })
        ;

        yield IntegerField::new('refundQuantity', '退货数量')
            ->setRequired(true)
            ->setHelp('商品退货数量')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('refundOrder', '退款订单'))
            ->add(TextFilter::new('merchantGoodsId', '商户商品编码'))
            ->add(TextFilter::new('wechatpayGoodsId', '微信商品编码'))
            ->add(TextFilter::new('goodsName', '商品名称'))
        ;
    }
}
