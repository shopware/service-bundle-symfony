<?php

namespace Shopware\ServiceBundle\Test\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\App\SDK\Context\ActionSource;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Shopware\App\SDK\Framework\Collection;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\ServiceBundle\Controller\LicenseController;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Exception\LicenseException;
use Shopware\ServiceBundle\Service\CommercialLicense;
use Shopware\ServiceBundle\Test\MockShopServiceRepository;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(LicenseController::class)]
#[CoversClass(LicenseException::class)]
class LicenseControllerTest extends TestCase
{
    private CommercialLicense&MockObject $commercialLicense;

    protected function setUp(): void
    {
        $this->commercialLicense = $this->createMock(CommercialLicense::class);
    }

    public function testSyncWithValidLicenseHost(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $request = $this->createMock(Request::class);
        $request->method('getPayload')->willReturn(new InputBag(['licenseHost' => 'valid_host']));

        $this->commercialLicense->expects($this->never())->method('validate');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testSyncWithValidLicenseKey(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $request = $this->createMock(Request::class);
        $request->method('getPayload')->willReturn(new InputBag(['licenseKey' => 'valid_key']));

        $this->commercialLicense->expects($this->once())->method('validate')->with('valid_key');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testSyncWithValidCredentials(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $request = $this->createMock(Request::class);
        $request->method('getPayload')->willReturn(new InputBag(['licenseKey' => 'valid_key', 'licenseHost' => 'valid_host']));

        $this->commercialLicense->expects($this->once())->method('validate')->with('valid_key');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testSyncWithInValidCredentials(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $request = $this->createMock(Request::class);
        $request->method('getPayload')->willReturn(new InputBag([]));

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);
        $content = $response->getContent();

        $this->assertIsString($content, 'Response content is not a valid string');
        $decodedContent = json_decode($content, true);

        $this->assertNotFalse($decodedContent, 'Failed to decode JSON content');
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals([
            'errors' => [
                'type' => 'missing_license_credentials',
                'detail' => 'No licenseKey and licenseHost provided',
            ],
        ], $decodedContent);
    }

    public function testSyncWithInvalidLicenseKey(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $request = $this->createMock(Request::class);
        $request->method('getPayload')->willReturn(new InputBag(['licenseKey' => 'invalid_key']));

        $this->commercialLicense->method('validate')->willThrowException(LicenseException::licenseInvalid('invalid_key'));

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);

        $dataExpected = [
            'errors' => [
                'type' => 'license_validation_failed',
                'detail' => 'License key not valid: invalid_key',
            ],
        ];

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals(json_encode($dataExpected), $response->getContent());
    }

    public function testSyncWithShopUpdateFailure(): void
    {
        $shop = new Shop('my-shop-id-1', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopServiceRepository();

        $request = $this->createMock(Request::class);
        $request->method('getPayload')->willReturn(new InputBag(['licenseKey' => 'valid_key']));

        $this->commercialLicense->expects($this->once())->method('validate')->with('valid_key');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);

        $content = $response->getContent();

        $this->assertIsString($content, 'Response content is not a valid string');
        $decodedContent = json_decode($content, true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals([
            'errors' => [
                'type' => 'shop_update_license_failed',
                'detail' => 'Shop with id "my-shop-id-1" not found',
            ],
        ], $decodedContent);
    }

    public function testProvidedWithValidCredentials(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop>&MockObject $shopRepository */
        $shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $shopRepository->expects($this->once())->method('updateShop')->with($shop);

        $this->commercialLicense->expects($this->once())->method('validate')->with('valid_key');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->provided($this->buildWebhookAction($shop, [
            'licenseKey' => 'valid_key',
            'licenseHost' => 'valid_host',
        ]));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
        $this->assertSame('valid_key', $shop->commercialLicenseKey);
        $this->assertSame('valid_host', $shop->commercialLicenseHost);
    }

    public function testProvidedWithMissingCredentialsReturns400(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop>&MockObject $shopRepository */
        $shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $shopRepository->expects($this->never())->method('updateShop');

        $this->commercialLicense->expects($this->never())->method('validate');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->provided($this->buildWebhookAction($shop, []));

        $content = $response->getContent();
        $this->assertIsString($content);
        $decodedContent = json_decode($content, true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals([
            'errors' => [
                'type' => 'missing_license_credentials',
                'detail' => 'No licenseKey and licenseHost provided',
            ],
        ], $decodedContent);
    }

    public function testProvidedWithInvalidLicenseKeyReturns403AndSkipsUpdate(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop>&MockObject $shopRepository */
        $shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $shopRepository->expects($this->never())->method('updateShop');

        $this->commercialLicense->method('validate')
            ->willThrowException(LicenseException::licenseInvalid('invalid_key'));

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->provided($this->buildWebhookAction($shop, [
            'licenseKey' => 'invalid_key',
            'licenseHost' => 'valid_host',
        ]));

        $dataExpected = [
            'errors' => [
                'type' => 'license_validation_failed',
                'detail' => 'License key not valid: invalid_key',
            ],
        ];

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals(json_encode($dataExpected), $response->getContent());
    }

    public function testProvidedWithShopUpdateFailureReturns500(): void
    {
        $shop = new Shop('my-shop-id-1', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopServiceRepository();

        $this->commercialLicense->expects($this->once())->method('validate')->with('valid_key');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->provided($this->buildWebhookAction($shop, [
            'licenseKey' => 'valid_key',
            'licenseHost' => 'valid_host',
        ]));

        $content = $response->getContent();
        $this->assertIsString($content);
        $decodedContent = json_decode($content, true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals([
            'errors' => [
                'type' => 'shop_update_license_failed',
                'detail' => 'Shop with id "my-shop-id-1" not found',
            ],
        ], $decodedContent);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function nonStringPayloadProvider(): iterable
    {
        yield 'licenseHost is array' => [['licenseKey' => 'valid_key', 'licenseHost' => []]];
        yield 'licenseHost is int' => [['licenseKey' => 'valid_key', 'licenseHost' => 42]];
        yield 'licenseHost is null' => [['licenseKey' => 'valid_key', 'licenseHost' => null]];
        yield 'licenseKey is array' => [['licenseKey' => [], 'licenseHost' => 'valid_host']];
        yield 'licenseKey is bool' => [['licenseKey' => true, 'licenseHost' => 'valid_host']];
        yield 'licenseKey is null' => [['licenseKey' => null, 'licenseHost' => 'valid_host']];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('nonStringPayloadProvider')]
    public function testProvidedReturns422OnNonStringPayloadFieldsAndDoesNotPersist(array $payload): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->commercialLicenseKey = 'pre-existing-key';
        $shop->commercialLicenseHost = 'pre-existing-host';

        /** @var ShopRepositoryInterface<Shop>&MockObject $shopRepository */
        $shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $shopRepository->expects($this->never())->method('updateShop');

        $this->commercialLicense->expects($this->never())->method('validate');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->provided($this->buildWebhookAction($shop, $payload));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('pre-existing-key', $shop->commercialLicenseKey);
        $this->assertSame('pre-existing-host', $shop->commercialLicenseHost);
    }

    public function testProvidedWithHostOnlyDoesNotValidateAndPersists(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');
        $shop->commercialLicenseKey = 'pre-existing-key';

        /** @var ShopRepositoryInterface<Shop>&MockObject $shopRepository */
        $shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $shopRepository->expects($this->once())->method('updateShop')->with($shop);

        $this->commercialLicense->expects($this->never())->method('validate');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->provided($this->buildWebhookAction($shop, [
            'licenseKey' => '',
            'licenseHost' => 'valid_host',
        ]));

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
        $this->assertSame('pre-existing-key', $shop->commercialLicenseKey);
        $this->assertSame('valid_host', $shop->commercialLicenseHost);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildWebhookAction(Shop $shop, array $payload): WebhookAction
    {
        return new WebhookAction(
            $shop,
            new ActionSource('https://shop.com', '6.7.11', new Collection()),
            'commercial_license.provided',
            $payload,
            new \DateTimeImmutable(),
        );
    }
}
