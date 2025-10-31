<?php

namespace WechatPayBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use WechatPayBundle\Entity\FundFlowBill;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Enum\AccountType;

/**
 * @extends AbstractCrudController<FundFlowBill>
 */
#[AdminCrud(routePath: '/wechat-pay/fund-flow-bill', routeName: 'wechat_pay_fund_flow_bill')]
final class FundFlowBillCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FundFlowBill::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('资金账单')
            ->setEntityLabelInPlural('资金账单管理')
            ->setPageTitle('index', '资金账单列表')
            ->setPageTitle('detail', '账单详情')
            ->setPageTitle('new', '创建资金账单')
            ->setPageTitle('edit', '编辑资金账单')
            ->setHelp('index', '管理微信支付资金账单信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['hashValue', 'localFile'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->hideOnForm();

        yield AssociationField::new('merchant', '商户')
            ->setRequired(true)
            ->setHelp('选择对应的微信支付商户')
        ;

        yield DateField::new('billDate', '账单日期')
            ->setRequired(true)
            ->setHelp('账单生成日期')
            ->setFormat('yyyy-MM-dd')
        ;

        yield ChoiceField::new('accountType', '账户类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AccountType::class])
            ->setRequired(true)
            ->setHelp('账户类型')
            ->formatValue(function ($value) {
                return $value instanceof AccountType ? $value->getLabel() : '';
            })
        ;

        yield TextField::new('hashType', '哈希类型')
            ->setRequired(true)
            ->setHelp('文件哈希算法类型')
        ;

        yield TextField::new('hashValue', '哈希值')
            ->setHelp('文件哈希值')
            ->hideOnIndex()
        ;

        yield UrlField::new('downloadUrl', '下载地址')
            ->setRequired(true)
            ->setHelp('微信返回的账单下载地址')
            ->hideOnIndex()
        ;

        yield TextField::new('localFile', '本地文件路径')
            ->setRequired(true)
            ->setHelp('本地存储的文件路径')
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
        $accountTypeChoices = [];
        foreach (AccountType::cases() as $case) {
            $accountTypeChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(EntityFilter::new('merchant', '商户'))
            ->add(ChoiceFilter::new('accountType', '账户类型')->setChoices($accountTypeChoices))
            ->add(TextFilter::new('hashType', '哈希类型'))
            ->add(DateTimeFilter::new('billDate', '账单日期'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
