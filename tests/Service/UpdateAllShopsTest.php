<?php

namespace Shopware\ServiceBundle\Test\Service;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Message\ShopUpdated;
use Shopware\ServiceBundle\Service\UpdateAllShops;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(UpdateAllShops::class)]
#[CoversClass(App::class)]
#[CoversClass(ShopUpdated::class)]
class UpdateAllShopsTest extends TestCase
{
    private AppSelector&MockObject $appSelector;
    private MessageBusInterface&MockObject $messageBus;

    /**
     * @var ObjectRepository<Shop>&MockObject
     */
    private ObjectRepository&MockObject $shopRepository;
    private UpdateAllShops $service;

    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->appSelector = $this->createMock(AppSelector::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->shopRepository = $this->createMock(ObjectRepository::class);
        $objectManager = $this->createMock(ObjectManager::class);

        $registry->method('getRepository')
            ->willReturn($this->shopRepository);
        $registry->method('getManager')
            ->willReturn($objectManager);

        $this->service = new UpdateAllShops(
            $registry,
            $this->appSelector,
            $this->messageBus,
        );
    }

    public function testMessageIsDispatchedForShopWhenUpdateIsAvailable(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->shopVersion = '1.0.0';
        $shop->selectedAppHash = 'aabbcc';

        $matcher = static::exactly(2);
        $this->shopRepository->expects($matcher)
            ->method('findBy')
            ->willReturnCallback(function () use ($matcher, $shop) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$shop],
                    2 => [],
                    default => null,
                };
            });

        $this->appSelector->method('select')
            ->with('1.0.0')
            ->willReturn(new App('/my/app', 'MyApp', '2.0.0', 'ddeeff'));

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ShopUpdated $message) use ($shop) {
                return $message->shopId === $shop->getShopId() && $message->toVersion === $shop->shopVersion;
            }))
            ->willReturn(new Envelope(new \stdClass()));


        $this->service->execute();
    }

    public function testMessageIsNotDispatchedForShopWhenUpdateIsNotAvailable(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->shopVersion = '1.0.0';
        $shop->selectedAppHash = 'aabbcc';

        $matcher = static::exactly(2);
        $this->shopRepository->expects($matcher)
            ->method('findBy')
            ->willReturnCallback(function () use ($matcher, $shop) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$shop],
                    2 => [],
                    default => null,
                };
            });

        $this->appSelector->method('select')
            ->with('1.0.0')
            ->willReturn(new App('/my/app', 'MyApp', '1.0.0', 'aabbcc'));

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->service->execute();
    }

    public function testMessageIsDispatchedForShopWithNoHashSet(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->shopVersion = '1.0.0';

        $matcher = static::exactly(2);
        $this->shopRepository->expects($matcher)
            ->method('findBy')
            ->willReturnCallback(function () use ($matcher, $shop) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$shop],
                    2 => [],
                    default => null,
                };
            });

        $this->appSelector->method('select')
            ->with('1.0.0')
            ->willReturn(new App('/my/app', 'MyApp', '2.0.0', 'ddeeff'));

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ShopUpdated $message) use ($shop) {
                return $message->shopId === $shop->getShopId() && $message->toVersion === $shop->shopVersion;
            }))
            ->willReturn(new Envelope(new \stdClass()));


        $this->service->execute();
    }
}
