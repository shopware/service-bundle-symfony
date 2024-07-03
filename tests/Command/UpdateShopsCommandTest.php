<?php declare(strict_types=1);

namespace Shopware\ServiceBundle\Test\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Command\UpdateShopsCommand;
use Shopware\ServiceBundle\Service\UpdateAllShops;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UpdateShopsCommand::class)]
class UpdateShopsCommandTest extends TestCase
{
    public function testUpdateShopsCommand(): void
    {
        $updater = $this->createMock(UpdateAllShops::class);
        $updater->expects($this->once())
            ->method('execute');

        $command = new UpdateShopsCommand($updater);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
