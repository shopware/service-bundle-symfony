<?php

namespace Shopware\ServiceBundle\Test\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
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

    public function testSyncWithValidLicenseKey(): void
    {
        $shop = new Shop('my-shop-id', 'https://shop.com', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $request = $this->createMock(Request::class);
        $request->method('getPayload')->willReturn(new InputBag(['licenseKey' => 'valid_key']));

        $this->commercialLicense->expects($this->once())->method('validate')->with($shop->getShopUrl(), 'valid_key');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testSyncWithMissingLicenseKey(): void
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
                'type' => 'missing_license_key',
                'detail' => 'No license key provided',
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

        $this->commercialLicense->expects($this->once())->method('validate')->with($shop->getShopUrl(), 'valid_key');

        $licenseController = new LicenseController($shopRepository, $this->commercialLicense);

        $response = $licenseController->sync($shop, $request);

        $content = $response->getContent();

        $this->assertIsString($content, 'Response content is not a valid string');
        $decodedContent = json_decode($content, true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals([
            'errors' => [
                'type' => 'shop_update_license_failed',
                'detail' => 'Failed to sync commercial license to shop',
            ],
        ], $decodedContent);
    }
}
