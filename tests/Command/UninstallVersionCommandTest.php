<?php

declare(strict_types=1);

namespace Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppLoader;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Command\UninstallVersionCommand;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Message\UninstallServiceFromShop;
use Shopware\ServiceBundle\ShopRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(UninstallVersionCommand::class)]
#[CoversClass(AppSelector::class)]
#[CoversClass(App::class)]
#[CoversClass(UninstallServiceFromShop::class)]
class UninstallVersionCommandTest extends TestCase
{
    public function testCommandFailsIfGivenVersionDoesNotExist(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);

        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->expects(static::once())->method('load')->willReturn([]);
        $appSelector = new AppSelector($appLoader);
        $repo = $this->createMock(ShopRepository::class);
        $command = new UninstallVersionCommand($repo, $appSelector, $bus);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['version' => '1.0.0']);

        static::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        static::assertStringContainsString(
            'Version "1.0.0" does not exist',
            $commandTester->getDisplay(),
        );
    }

    public function testUninstallVersionOnlyUninstallsActiveShopsForTheGivenVersion(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $matcher = $this->exactly(2);

        $bus->expects($matcher)
            ->method('dispatch')
            ->willReturnCallback(function (UninstallServiceFromShop $msg) use ($matcher) {
                static::assertEquals([1 => 'id-1', 2 => 'id-2'][$matcher->numberOfInvocations()], $msg->shopId);
                return new Envelope(new \stdClass());
            });

        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->expects(static::once())->method('load')->willReturn([
            new App('/', 'MyApp', '1.0.0', '1.0.0'),
            new App('/', 'MyApp', '2.0.0', '2.0.0'),
        ]);
        $appSelector = new AppSelector($appLoader);
        $repo = $this->createMock(ShopRepository::class);
        $repo->expects(static::once())
            ->method('findActiveShopsForVersion')
            ->with('1.0.0')
            ->willReturnCallback(function (): \Generator {
                $shop = new Shop('id-1', '/', 'secret');
                $shop->setShopActive(true);
                $shop->selectedAppVersion = '1.0.0';

                yield $shop;

                $shop = new Shop('id-2', '/', 'secret');
                $shop->setShopActive(true);
                $shop->selectedAppVersion = '1.0.0';

                yield $shop;
            });

        $command = new UninstallVersionCommand($repo, $appSelector, $bus);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['version' => '1.0.0']);

        $commandTester->assertCommandIsSuccessful();

        static::assertStringContainsString(
            'Attempting to uninstall service version "1.0.0" from "2" shops',
            $commandTester->getDisplay(),
        );
    }
}
