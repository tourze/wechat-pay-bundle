<?php

namespace WechatPayBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use WechatPayBundle\Controller\Admin\MerchantCrudController;

/**
 * @internal
 */
#[CoversClass(MerchantCrudController::class)]
#[RunTestsInSeparateProcesses]
final class MerchantCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): MerchantCrudController
    {
        return new MerchantCrudController();
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '状态' => ['状态'];
        yield '商户号' => ['商户号'];
        yield '备注' => ['备注'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'valid' => ['valid'];
        yield 'mchId' => ['mchId'];
        yield 'apiKey' => ['apiKey'];
        yield 'pemKey' => ['pemKey'];
        yield 'certSerial' => ['certSerial'];
        yield 'pemCert' => ['pemCert'];
        yield 'remark' => ['remark'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'valid' => ['valid'];
        yield 'mchId' => ['mchId'];
        yield 'apiKey' => ['apiKey'];
        yield 'pemKey' => ['pemKey'];
        yield 'certSerial' => ['certSerial'];
        yield 'pemCert' => ['pemCert'];
        yield 'remark' => ['remark'];
    }

    public function testControllerClassStructure(): void
    {
        $controller = self::getService(MerchantCrudController::class);
        $this->assertInstanceOf(MerchantCrudController::class, $controller);

        $entityFqcn = $controller::getEntityFqcn();
        $this->assertSame('WechatPayBundle\Entity\Merchant', $entityFqcn);
    }

    public function testRequiredFieldValidation(): void
    {
        $client = self::createClientWithDatabase();

        // Create and login as admin user
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        // Access the new page
        $crawler = $client->request('GET', '/admin/wechat-pay/merchant/new');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Get form and submit with empty required fields
        $button = $crawler->selectButton('创建');
        if (0 === $button->count()) {
            $button = $crawler->selectButton('Create');
        }
        if (0 === $button->count()) {
            $button = $crawler->selectButton('Save');
        }
        if (0 === $button->count()) {
            $button = $crawler->filter('button[type="submit"]');
        }
        $this->assertGreaterThan(0, $button->count(), 'No submit button found');
        $form = $button->form();

        // Submit with required fields empty
        $form['Merchant[mchId]'] = '';
        $form['Merchant[apiKey]'] = '';
        $form['Merchant[certSerial]'] = '';

        $crawler = $client->submit($form);

        // Verify validation failure
        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $feedbackText = $crawler->filter('.invalid-feedback')->text();
        $this->assertTrue(
            str_contains($feedbackText, 'should not be blank') || str_contains($feedbackText, '不能为空'),
            'Validation error message should contain blank validation text'
        );
    }
}
