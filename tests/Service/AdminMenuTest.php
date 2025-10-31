<?php

namespace WechatPayBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use WechatPayBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // AdminMenu 测试不需要特殊的设置
    }

    public function testServiceCreation(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testImplementsMenuProviderInterface(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(MenuProviderInterface::class, $adminMenu);
    }

    public function testInvokeShouldBeCallable(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $reflection = new \ReflectionClass($adminMenu);
        $this->assertTrue($reflection->hasMethod('__invoke'));
    }

    public function testInvoke(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $item = $this->createMock(ItemInterface::class);
        $childItem = $this->createMock(ItemInterface::class);
        $subMenuItem1 = $this->createMock(ItemInterface::class);
        $subMenuItem2 = $this->createMock(ItemInterface::class);
        $subMenuItem3 = $this->createMock(ItemInterface::class);
        $subMenuItem4 = $this->createMock(ItemInterface::class);
        $subMenuItem5 = $this->createMock(ItemInterface::class);
        $subMenuItem6 = $this->createMock(ItemInterface::class);

        $item->expects($this->exactly(2))
            ->method('getChild')
            ->with('微信支付')
            ->willReturnOnConsecutiveCalls(null, $childItem)
        ;

        $item->expects($this->once())
            ->method('addChild')
            ->with('微信支付')
            ->willReturn($childItem)
        ;

        $childItem->expects($this->exactly(6))
            ->method('addChild')
            ->willReturnOnConsecutiveCalls(
                $subMenuItem1,
                $subMenuItem2,
                $subMenuItem3,
                $subMenuItem4,
                $subMenuItem5,
                $subMenuItem6
            )
        ;

        // 设置链式调用的期望：每个子菜单项调用setUri后返回自身，然后调用setAttribute
        $subMenuItem1->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem1)
        ;
        $subMenuItem1->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-store')
            ->willReturn($subMenuItem1)
        ;

        $subMenuItem2->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem2)
        ;
        $subMenuItem2->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-credit-card')
            ->willReturn($subMenuItem2)
        ;

        $subMenuItem3->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem3)
        ;
        $subMenuItem3->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-undo')
            ->willReturn($subMenuItem3)
        ;

        $subMenuItem4->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem4)
        ;
        $subMenuItem4->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-list-ul')
            ->willReturn($subMenuItem4)
        ;

        $subMenuItem5->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem5)
        ;
        $subMenuItem5->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-money-bill-wave')
            ->willReturn($subMenuItem5)
        ;

        $subMenuItem6->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem6)
        ;
        $subMenuItem6->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-file-invoice-dollar')
            ->willReturn($subMenuItem6)
        ;

        $adminMenu($item);
    }
}
