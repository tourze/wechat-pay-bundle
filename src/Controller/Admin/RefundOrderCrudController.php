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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Entity\RefundOrder;

/**
 * @extends AbstractCrudController<RefundOrder>
 */
#[AdminCrud(routePath: '/wechat-pay/refund-order', routeName: 'wechat_pay_refund_order')]
final class RefundOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RefundOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('退款订单')
            ->setEntityLabelInPlural('退款订单管理')
            ->setPageTitle('index', '退款订单列表')
            ->setPageTitle('detail', '退款详情')
            ->setPageTitle('new', '创建退款订单')
            ->setPageTitle('edit', '编辑退款订单')
            ->setHelp('index', '管理微信支付退款订单信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['refundId', 'reason', 'status'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->hideOnForm();

        yield AssociationField::new('payOrder', '支付订单')
            ->setRequired(true)
            ->setHelp('选择对应的支付订单')
        ;

        yield TextField::new('appId', 'AppID')
            ->setRequired(true)
            ->setHelp('微信应用ID')
        ;

        yield TextField::new('reason', '退款原因')
            ->setHelp('退款原因说明')
        ;

        yield TextField::new('notifyUrl', '回调地址')
            ->setHelp('退款结果回调URL')
            ->hideOnIndex()
        ;

        yield TextField::new('currency', '退款币种')
            ->setHelp('退款币种，默认为CNY')
        ;

        yield IntegerField::new('money', '退款金额（分）')
            ->setRequired(true)
            ->setHelp('退款金额，单位为分')
            ->formatValue(function (mixed $value): string {
                if (is_int($value) && $value > 0) {
                    return number_format($value / 100, 2) . ' 元';
                }

                return '0.00 元';
            })
        ;

        yield TextField::new('refundId', '微信退款单号')
            ->setHelp('微信支付退款单号')
            ->hideOnIndex()
        ;

        yield TextField::new('refundChannel', '退款渠道')
            ->setHelp('退款渠道信息')
            ->hideOnIndex()
        ;

        yield TextField::new('userReceiveAccount', '退款入账账户')
            ->setHelp('退款入账账户信息')
            ->hideOnIndex()
        ;

        yield TextField::new('status', '退款状态')
            ->setHelp('退款订单状态')
        ;

        yield DateTimeField::new('successTime', '退款成功时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('退款成功时间')
            ->hideOnIndex()
        ;

        yield TextareaField::new('requestJson', '请求数据')
            ->setHelp('向微信发送的请求数据')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(function (mixed $value): string {
                return is_string($value) && '' !== $value ? $this->formatJson($value) : '无';
            })
        ;

        yield TextareaField::new('responseJson', '响应数据')
            ->setHelp('微信返回的响应数据')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(function (mixed $value): string {
                return is_string($value) && '' !== $value ? $this->formatJson($value) : '无';
            })
        ;

        yield TextareaField::new('callbackResponse', '回调数据')
            ->setHelp('微信退款结果回调数据')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(function (mixed $value): string {
                return is_string($value) && '' !== $value ? $this->formatJson($value) : '无';
            })
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
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('payOrder', '支付订单'))
            ->add(TextFilter::new('refundId', '微信退款单号'))
            ->add(TextFilter::new('status', '退款状态'))
            ->add(TextFilter::new('reason', '退款原因'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('successTime', '退款成功时间'))
        ;
    }

    private function formatJson(?string $json): string
    {
        if (null === $json || '' === $json) {
            return '无';
        }

        $decoded = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return $json;
        }

        $result = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return false !== $result ? $result : $json;
    }
}
