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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Entity\PayOrder;
use WechatPayBundle\Enum\PayOrderStatus;

/**
 * @extends AbstractCrudController<PayOrder>
 */
#[AdminCrud(routePath: '/wechat-pay/pay-order', routeName: 'wechat_pay_pay_order')]
final class PayOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PayOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('支付订单')
            ->setEntityLabelInPlural('支付订单管理')
            ->setPageTitle('index', '支付订单列表')
            ->setPageTitle('detail', '订单详情')
            ->setPageTitle('new', '创建支付订单')
            ->setPageTitle('edit', '编辑支付订单')
            ->setHelp('index', '管理微信支付订单信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['tradeNo', 'body', 'transactionId'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999)->hideOnForm();

        yield AssociationField::new('merchant', '商户')
            ->setHelp('选择对应的微信支付商户')
        ;

        yield AssociationField::new('parent', '父订单')
            ->setHelp('如果是子订单，选择对应的父订单')
            ->hideOnIndex()
        ;

        yield TextField::new('appId', 'AppID')
            ->setRequired(true)
            ->setHelp('微信应用ID')
        ;

        yield TextField::new('mchId', '商户ID')
            ->setRequired(true)
            ->setHelp('微信支付商户ID')
        ;

        yield TextField::new('tradeType', '交易类型')
            ->setRequired(true)
            ->setHelp('交易类型：JSAPI、NATIVE、APP等')
        ;

        yield TextField::new('tradeNo', '商户订单号')
            ->setRequired(true)
            ->setHelp('商户系统内部订单号')
        ;

        yield TextField::new('body', '商品描述')
            ->setRequired(true)
            ->setHelp('商品简单描述')
        ;

        yield TextField::new('feeType', '货币类型')
            ->setRequired(true)
            ->setHelp('标价币种，默认为CNY')
        ;

        yield IntegerField::new('totalFee', '金额（分）')
            ->setRequired(true)
            ->setHelp('订单总金额，单位为分')
            ->formatValue(function (mixed $value): string {
                if (\is_int($value) && $value > 0) {
                    $yuan = $value / 100;

                    return number_format($yuan, 2) . ' 元';
                }

                return '0.00 元';
            })
        ;

        yield DateTimeField::new('startTime', '交易起始时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('订单生成时间')
        ;

        yield DateTimeField::new('expireTime', '交易结束时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('订单失效时间')
        ;

        yield TextField::new('notifyUrl', '通知地址')
            ->setRequired(true)
            ->setHelp('异步接收微信支付结果通知的回调地址')
            ->hideOnIndex()
        ;

        yield TextField::new('openId', '用户标识')
            ->setHelp('微信用户在商户appid下的唯一标识')
            ->hideOnIndex()
        ;

        yield TextField::new('attach', '附加数据')
            ->setHelp('附加数据，在查询API和支付通知中原样返回')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('status', '订单状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => PayOrderStatus::class])
            ->formatValue(function ($value) {
                return $value instanceof PayOrderStatus ? $value->getLabel() : '';
            })
        ;

        yield TextField::new('transactionId', '微信支付流水号')
            ->setHelp('微信支付订单号')
            ->hideOnIndex()
        ;

        yield TextField::new('tradeState', '微信支付状态')
            ->setHelp('微信支付交易状态')
            ->hideOnIndex()
        ;

        yield TextField::new('prepayId', '预支付会话标识')
            ->setHelp('微信生成的预支付会话标识')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('prepayExpireTime', '预支付过期时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('预支付交易会话过期时间')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('callbackTime', '回调时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('微信支付结果回调时间')
            ->hideOnIndex()
        ;

        yield TextareaField::new('requestJson', '请求数据')
            ->setHelp('向微信发送的请求数据')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(function (mixed $value): string {
                if (!\is_string($value) || '' === $value) {
                    return '无';
                }

                return $this->formatJson($value);
            })
        ;

        yield TextareaField::new('responseJson', '响应数据')
            ->setHelp('微信返回的响应数据')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(function (mixed $value): string {
                if (!\is_string($value) || '' === $value) {
                    return '无';
                }

                return $this->formatJson($value);
            })
        ;

        yield TextareaField::new('callbackResponse', '回调数据')
            ->setHelp('微信支付结果回调数据')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(function (mixed $value): string {
                if (!\is_string($value) || '' === $value) {
                    return '无';
                }

                return $this->formatJson($value);
            })
        ;

        yield TextField::new('description', '描述')
            ->setHelp('订单描述信息')
            ->hideOnIndex()
        ;

        yield TextField::new('remark', '备注')
            ->setHelp('订单备注信息')
            ->hideOnIndex()
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
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statusChoices = [];
        foreach (PayOrderStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(EntityFilter::new('merchant', '商户'))
            ->add(TextFilter::new('tradeNo', '商户订单号'))
            ->add(TextFilter::new('transactionId', '微信支付流水号'))
            ->add(ChoiceFilter::new('status', '订单状态')->setChoices($statusChoices))
            ->add(TextFilter::new('tradeType', '交易类型'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('callbackTime', '回调时间'))
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
