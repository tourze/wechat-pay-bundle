<?php

namespace WechatPayBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use WechatPayBundle\Entity\Merchant;

/**
 * @extends AbstractCrudController<Merchant>
 */
#[AdminCrud(routePath: '/wechat-pay/merchant', routeName: 'wechat_pay_merchant')]
final class MerchantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Merchant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('微信支付商户')
            ->setEntityLabelInPlural('微信支付商户管理')
            ->setPageTitle('index', '微信支付商户列表')
            ->setPageTitle('detail', '商户详情')
            ->setPageTitle('new', '添加新商户')
            ->setPageTitle('edit', '编辑商户')
            ->setHelp('index', '管理微信支付商户配置信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['mchId', 'remark'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->hideOnForm();

        yield BooleanField::new('valid', '状态')
            ->setHelp('启用或禁用此商户')
        ;

        yield TextField::new('mchId', '商户号')
            ->setRequired(true)
            ->setHelp('微信支付分配的商户号')
        ;

        yield TextField::new('apiKey', 'API密钥')
            ->setRequired(true)
            ->setHelp('微信支付API密钥')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ? '***已设置***' : '未设置';
            })
        ;

        yield TextareaField::new('pemKey', '商户API私钥')
            ->setHelp('商户API私钥文件内容')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ? '***已设置***' : '未设置';
            })
        ;

        yield TextField::new('certSerial', '证书序列号')
            ->setRequired(true)
            ->setHelp('商户API证书序列号')
            ->hideOnIndex()
        ;

        yield TextareaField::new('pemCert', '微信支付平台证书')
            ->setHelp('微信支付平台证书内容')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ? '***已设置***' : '未设置';
            })
        ;

        yield TextField::new('remark', '备注')
            ->setHelp('商户备注信息')
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
            ->add(BooleanFilter::new('valid', '状态'))
            ->add(TextFilter::new('mchId', '商户号'))
            ->add(TextFilter::new('remark', '备注'))
        ;
    }
}
