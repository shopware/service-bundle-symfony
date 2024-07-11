<?php

namespace Shopware\ServiceBundle\Test\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Shopware\App\SDK\Context\ActionSource;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\App\AppZipper;
use Shopware\ServiceBundle\Controller\LifecycleController;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Message\ShopUpdated;
use Shopware\ServiceBundle\Service\ShopUpdater;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(LifecycleController::class)]
#[CoversClass(ShopUpdated::class)]
#[CoversClass(App::class)]
class LifecycleControllerTest extends TestCase
{
    public function testSelectAppSelectAppropriateAppVersionAndReturnsInfo(): void
    {
        $appSelector = $this->createMock(AppSelector::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $controller = new LifecycleController(
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            $appSelector,
            $this->createMock(ShopUpdater::class),
            $urlGenerator,
            $this->createMock(AppZipper::class),
        );

        $app = new App('/path/to/app', 'MyCoolService', '6.6.0.0', 'aabbcc');

        $appSelector->expects(static::once())
            ->method('select')
            ->with('6.6.0.0')
            ->willReturn($app);

        $urlGenerator->expects(static::once())
            ->method('generate')
            ->with('shopware_service_lifecycle_app_zip', ['version' => '6.6.0.0'])
            ->willReturn('/download/link/for/app');

        $response = $controller->selectApp('6.6.0.0');

        static::assertEquals(200, $response->getStatusCode());
        static::assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode((string) $response->getContent(), true);

        static::assertIsArray($data);
        static::assertSame(['app-version', 'app-revision', 'app-hash', 'app-zip-url'], array_keys($data));
        static::assertSame('6.6.0.0', $data['app-version']);
        static::assertSame('6.6.0.0-aabbcc', $data['app-revision']);
        static::assertSame('aabbcc', $data['app-hash']);
        static::assertSame('/download/link/for/app', $data['app-zip-url']);
    }

    public function testGetAppZipReturnsDownloadResponse(): void
    {
        $appSelector = $this->createMock(AppSelector::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $appZipper = $this->createMock(AppZipper::class);

        $controller = new LifecycleController(
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            $appSelector,
            $this->createMock(ShopUpdater::class),
            $urlGenerator,
            $appZipper,
        );

        $app = new App('/path/to/app', 'MyCoolService', '6.6.0.0', 'aabbcc');

        $appSelector->expects(static::once())
            ->method('specific')
            ->with('6.6.0.0')
            ->willReturn($app);

        $appZipper->expects(static::once())
            ->method('zip')
            ->with($app)
            ->willReturn('zip-content');

        $urlGenerator->expects(static::once())
            ->method('generate')
            ->with('shopware_service_lifecycle_app_zip', ['version' => '6.6.0.0'])
            ->willReturn('/download/link/for/app');

        $response = $controller->getAppZip('6.6.0.0');

        static::assertEquals(200, $response->getStatusCode());

        static::assertSame('zip-content', $response->getContent());

        static::assertSame('application/zip', $response->headers->get('Content-Type'));
        static::assertSame('attachment; filename="MyCoolService.zip"', $response->headers->get('Content-Disposition'));
        static::assertSame('6.6.0.0', $response->headers->get('sw-app-version'));
        static::assertSame('6.6.0.0-aabbcc', $response->headers->get('sw-app-revision'));
        static::assertSame('aabbcc', $response->headers->get('sw-app-hash'));
        static::assertSame('/download/link/for/app', $response->headers->get('sw-app-zip-url'));
    }


    public function testReportUpdateReturns422WithInvalidPayload(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $controller = new LifecycleController(
            $this->createMock(MessageBusInterface::class),
            $logger,
            $this->createMock(AppSelector::class),
            $this->createMock(ShopUpdater::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(AppZipper::class),
        );

        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        $messageBus->expects(static::never())->method('dispatch');
        $logger->expects(static::never())->method('info');

        $request = new WebhookAction(
            $shop,
            new ActionSource('https://shop.com', '2.0.0'),
            'shopware.updated',
            [],
            new \DateTimeImmutable(),
        );

        $response = $controller->reportUpdate($request);

        static::assertSame(422, $response->getStatusCode());
    }


    public function testReportUpdateDispatchesMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $messageBus = $this->createMock(MessageBusInterface::class);

        $controller = new LifecycleController(
            $messageBus,
            $logger,
            $this->createMock(AppSelector::class),
            $this->createMock(ShopUpdater::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(AppZipper::class),
        );

        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        $logger->expects(static::once())
            ->method('info')
            ->with('Reporting update for shop: "my-shop-id" to version: "6.7.0.0"');

        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ShopUpdated $message) {
                return $message->shopId === 'my-shop-id' && $message->toVersion === '6.7.0.0';
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $request = new WebhookAction(
            $shop,
            new ActionSource('https://shop.com', '2.0.0'),
            'shopware.updated',
            [
                'newVersion' => '6.7.0.0',
            ],
            new \DateTimeImmutable(),
        );

        $response = $controller->reportUpdate($request);

        static::assertSame(204, $response->getStatusCode());

    }

    /**
     * @return iterable<list<array<string, mixed>>>
     */
    public static function updateFinishedInvalidPayloadProvider(): iterable
    {
        yield 'empty payload' => [[]];

        yield 'missing appVersion' => [['appHash' => 'aabbcc']];

        yield 'wrong values' => [['appVersion' => 1]];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('updateFinishedInvalidPayloadProvider')]
    public function testServiceUpdateFinishedReturns422WithInvalidPayload(array $payload): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $shopUpdater = $this->createMock(ShopUpdater::class);

        $controller = new LifecycleController(
            $this->createMock(MessageBusInterface::class),
            $logger,
            $this->createMock(AppSelector::class),
            $shopUpdater,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(AppZipper::class),
        );

        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        $logger->expects(static::never())->method('info');

        $shopUpdater->expects(static::never())
            ->method('markShopUpdated')
            ->with($shop, '2.0.0', 'aabbcc');

        $request = new WebhookAction(
            $shop,
            new ActionSource('https://shop.com', '2.0.0'),
            'shop.service.updated',
            $payload,
            new \DateTimeImmutable(),
        );

        $response = $controller->serviceUpdateFinished($request);

        static::assertSame(422, $response->getStatusCode());
    }

    public function testServiceUpdateFinishedMarksShopAsUpdated(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $shopUpdater = $this->createMock(ShopUpdater::class);

        $controller = new LifecycleController(
            $this->createMock(MessageBusInterface::class),
            $logger,
            $this->createMock(AppSelector::class),
            $shopUpdater,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(AppZipper::class),
        );

        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        $logger->expects(static::once())
            ->method('info')
            ->with('Service was updated for shop: "my-shop-id"');

        $shopUpdater->expects(static::once())
            ->method('markShopUpdated')
            ->with($shop, '2.0.0', 'aabbcc');

        $request = new WebhookAction(
            $shop,
            new ActionSource('https://shop.com', '2.0.0'),
            'shopware.service.updated',
            ['appVersion' => '2.0.0-aabbcc'],
            new \DateTimeImmutable(),
        );

        $response = $controller->serviceUpdateFinished($request);

        static::assertSame(204, $response->getStatusCode());
    }
}
