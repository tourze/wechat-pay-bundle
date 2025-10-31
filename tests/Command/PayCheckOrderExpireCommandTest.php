<?php

namespace WechatPayBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use WechatPayBundle\Command\PayCheckOrderExpireCommand;

/**
 * @internal
 */
#[CoversClass(PayCheckOrderExpireCommand::class)]
#[RunTestsInSeparateProcesses]
final class PayCheckOrderExpireCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    /**
     * 测试命令执行基本流程
     */
    public function testCommandExecute(): void
    {
        // 命令执行可能因为缺少过期订单数据而成功但无实际处理
        $exitCode = $this->commandTester->execute([]);

        // 验证命令执行完成（0为成功状态码）
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $command = self::getContainer()->get(PayCheckOrderExpireCommand::class);
        $this->assertInstanceOf(Command::class, $command);

        $application = new Application();
        $application->add($command);

        $command = $application->find('wechat:pay:check-order-expire');
        $this->commandTester = new CommandTester($command);
    }
}
