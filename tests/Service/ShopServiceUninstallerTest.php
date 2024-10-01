<?php

namespace Shopware\ServiceBundle\Test\Service;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\HttpClient\SimpleHttpClient\SimpleHttpClient;
use Shopware\App\SDK\HttpClient\SimpleHttpClient\SimpleHttpClientResponse;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Service\ShopServiceUninstaller;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShopServiceUninstaller::class)]
class ShopServiceUninstallerTest extends TestCase
{
    public function testRunThrowsExceptionWhenShopDoesNotExist(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Shop with id "my-shop-id" not found');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $shopUpdater = new ShopServiceUninstaller(
            $shopRepository,
            new AppConfiguration('MyApp', 'app-secret', '/confirm'),
            $clientFactory,
            $logger,
        );

        $shopUpdater->run('my-shop-id');
    }

    public function testShopIsNotDeactivatedIfUninstallFails(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->setShopActive(true);
        $shop->selectedAppVersion = '6.6.0.0';
        $shop->selectedAppHash = 'aaa';

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $client = static::createMock(SimpleHttpClient::class);
        $client->expects(static::once())
            ->method('post')
            ->with('https://shop.com/api/service/uninstall/MyApp')
            ->willReturn(new SimpleHttpClientResponse(new Response(400)));

        $clientFactory->expects(static::once())
            ->method('createSimpleClient')
            ->willReturn($client);

        $shopUpdater = new ShopServiceUninstaller(
            $shopRepository,
            new AppConfiguration('MyApp', 'app-secret', '/confirm'),
            $clientFactory,
            $logger,
        );

        $shopUpdater->run('my-shop-id');

        static::assertSame(true, $shop->isShopActive());
        static::assertSame('6.6.0.0', $shop->selectedAppVersion);
        static::assertSame('aaa', $shop->selectedAppHash);
    }

    public function testRunDeactivatesShopAfterUninstallation(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->setShopActive(true);
        $shop->selectedAppVersion = '6.6.0.0';
        $shop->selectedAppHash = 'aaa';

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $clientFactory = static::createMock(ClientFactory::class);
        $logger = static::createMock(LoggerInterface::class);

        $client = static::createMock(SimpleHttpClient::class);
        $client->expects(static::once())
            ->method('post')
            ->with('https://shop.com/api/service/uninstall/MyApp')
            ->willReturn(new SimpleHttpClientResponse(new Response()));

        $clientFactory->expects(static::once())
            ->method('createSimpleClient')
            ->willReturn($client);

        $shopUpdater = new ShopServiceUninstaller(
            $shopRepository,
            new AppConfiguration('MyApp', 'app-secret', '/confirm'),
            $clientFactory,
            $logger,
        );

        $shopUpdater->run('my-shop-id');

        static::assertSame(false, $shop->isShopActive());
        static::assertNull($shop->selectedAppVersion);
        static::assertNull($shop->selectedAppHash);
    }
}
