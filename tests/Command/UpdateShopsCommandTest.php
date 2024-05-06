<?php

namespace Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Command\UpdateShopsCommand;
use Shopware\ServiceBundle\Service\ShopUpdater;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UpdateShopsCommand::class)]
class UpdateShopsCommandTest extends TestCase
{
    public function testUpdateShopsCommand(): void
    {
        $updater = $this->createMock(ShopUpdater::class);
        $updater->expects($this->once())
            ->method('execute');

        $command = new UpdateShopsCommand($updater);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
