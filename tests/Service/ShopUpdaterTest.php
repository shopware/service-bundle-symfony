<?php

namespace Shopware\ServiceBundle\Test\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\HttpClient\SimpleHttpClient\SimpleHttpClient;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\App\NoSupportedAppException;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Service\ShopUpdater;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShopUpdater::class)]
#[CoversClass(App::class)]
#[CoversClass(NoSupportedAppException::class)]
class ShopUpdaterTest extends TestCase
{
    public function testRunThrowsExceptionWhenShopDoesNotExist(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Shop with id "my-shop-id" not found');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $appSelector = static::createMock(AppSelector::class);
        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $shopUpdater = new ShopUpdater(
            $shopRepository,
            $appSelector,
            $clientFactory,
            $logger,
        );

        $shopUpdater->run('my-shop-id', '6.6.0.0');
    }

    public function testRunThrowsExceptionWhenNoCompatibleAppExistsForShopwareVersion(): void
    {
        static::expectException(NoSupportedAppException::class);
        static::expectExceptionMessage('Could not find a supported app for Shopware version "6.6.0.0"');

        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $appSelector = static::createMock(AppSelector::class);
        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $appSelector->expects(static::once())
            ->method('select')
            ->with('6.6.0.0')
            ->willThrowException(NoSupportedAppException::fromShopwareVersion('6.6.0.0'));

        $shopUpdater = new ShopUpdater(
            $shopRepository,
            $appSelector,
            $clientFactory,
            $logger,
        );

        $shopUpdater->run('my-shop-id', '6.6.0.0');
    }

    public function testRunLogsAndSkipsUpdateIfNoNewVersionAvailable(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->shopVersion = '6.6.0.0';
        $shop->selectedAppVersion = '6.6.0.0';
        $shop->selectedAppHash = 'aabbccdd';

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $appSelector = static::createMock(AppSelector::class);
        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $appSelector->expects(static::once())
            ->method('select')
            ->with('6.6.0.0')
            ->willReturn(new App('/some/path', 'MyCoolService', '6.6.0.0', 'aabbccdd'));

        $clientFactory->expects(static::never())->method('createSimpleClient');

        $logger->expects(static::once())
            ->method('debug')
            ->with('No new app available for shop "my-shop-id" running Shopware "6.6.0.0" - using app version "6.6.0.0"');

        $shopUpdater = new ShopUpdater(
            $shopRepository,
            $appSelector,
            $clientFactory,
            $logger,
        );

        $shopUpdater->run('my-shop-id', '6.6.0.0');
    }

    public function testRunSendsTriggersUpdateViaPlatformApiWhenNewVersionAvailable(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->shopVersion = '6.6.0.0';
        $shop->selectedAppVersion = '6.6.0.0';
        $shop->selectedAppHash = 'aabbccdd';

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $appSelector = static::createMock(AppSelector::class);
        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $appSelector->expects(static::once())
            ->method('select')
            ->with('6.7.0.0')
            ->willReturn(new App('/some/path', 'MyCoolService', '6.7.0.0', 'eeffgg'));

        $client = static::createMock(SimpleHttpClient::class);

        $client->expects(static::once())
            ->method('post')
            ->with('https://shop.com/api/services/trigger-update');

        $clientFactory->expects(static::once())
            ->method('createSimpleClient')
            ->willReturn($client);

        $logger->expects(static::once())
            ->method('info')
            ->with('New version of app "6.7.0.0" for Shopware version "6.7.0.0" available');

        $shopUpdater = new ShopUpdater(
            $shopRepository,
            $appSelector,
            $clientFactory,
            $logger,
        );

        $shopUpdater->run('my-shop-id', '6.7.0.0');
    }

    public function testMarkShopAsUpdated(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->shopVersion = '6.7.0.0';
        $shop->selectedAppVersion = '6.6.0.0';
        $shop->selectedAppHash = 'aabbccdd';

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();

        $appSelector = static::createMock(AppSelector::class);
        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $shopUpdater = new ShopUpdater(
            $shopRepository,
            $appSelector,
            $clientFactory,
            $logger,
        );

        $shopUpdater->markShopUpdated($shop, '6.7.0.0', 'eeffgg');

        /** @var Shop $shop */
        $shop = $shopRepository->getShopFromId('my-shop-id');
        static::assertSame('6.7.0.0', $shop->shopVersion);
        static::assertSame('6.7.0.0', $shop->selectedAppVersion);
        static::assertSame('eeffgg', $shop->selectedAppHash);
    }
}
