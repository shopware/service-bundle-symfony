<?php

declare(strict_types=1);

namespace Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppLoader;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Command\CanCleanupVersionCommand;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\ShopRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CanCleanupVersionCommand::class)]
#[CoversClass(AppSelector::class)]
#[CoversClass(App::class)]
class CanCleanupVersionCommandTest extends TestCase
{
    public function testCommandFailsIfGivenVersionDoesNotExist(): void
    {
        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->expects(static::once())->method('load')->willReturn([]);
        $appSelector = new AppSelector($appLoader);
        $repo = $this->createMock(ShopRepository::class);
        $command = new CanCleanupVersionCommand($repo, $appSelector);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['version' => '1.0.0']);

        static::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        static::assertStringContainsString(
            'Version "1.0.0" does not exist',
            $commandTester->getDisplay(),
        );
    }

    public function testCommandFailsWhenVersionsExist(): void
    {
        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->expects(static::any())->method('load')->willReturn([
            new App('/', 'MyApp', '1.0.0', '1.0.0'),
            new App('/', 'MyApp', '2.0.0', '2.0.0'),
            new App('/', 'MyApp', '3.0.0', '3.0.0'),
        ]);
        $appSelector = new AppSelector($appLoader);
        $repo = $this->createMock(ShopRepository::class);

        $repo->expects(static::any())
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

        $command = new CanCleanupVersionCommand($repo, $appSelector);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['version' => '1.0.0']);

        static::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        static::assertStringContainsString(
            '"2" shops registered on version "1.0.0". Version cannot be removed.',
            $commandTester->getDisplay(),
        );
    }

    public function testCommandPassesWhenVersionsDoNotExist(): void
    {
        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->expects(static::any())->method('load')->willReturn([
            new App('/', 'MyApp', '1.0.0', '1.0.0'),
            new App('/', 'MyApp', '2.0.0', '2.0.0'),
            new App('/', 'MyApp', '3.0.0', '3.0.0'),
        ]);
        $appSelector = new AppSelector($appLoader);
        $repo = $this->createMock(ShopRepository::class);

        $repo->expects(static::any())
            ->method('findActiveShopsForVersion')
            ->with('3.0.0')
            ->willReturnCallback(function (): \Generator {
                yield from [];
            });

        $command = new CanCleanupVersionCommand($repo, $appSelector);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['version' => '3.0.0']);

        $commandTester->assertCommandIsSuccessful();
        static::assertStringContainsString(
            'No shops registered on version "3.0.0". Version can be safely removed.',
            $commandTester->getDisplay(),
        );
    }
}
