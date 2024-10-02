<?php

namespace Shopware\ServiceBundle\Test\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Test\MockShopRepository;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Exception\LicenseException;
use Shopware\ServiceBundle\Service\CommercialLicense;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\Service\EmptyKey;
use Shopware\ServiceBundle\Service\LicenseInfo;

#[CoversClass(CommercialLicense::class)]
#[CoversClass(LicenseException::class)]
#[CoversClass(EmptyKey::class)]
#[CoversClass(LicenseInfo::class)]
class CommercialLicenseTest extends TestCase
{
    private const LICENSE_KEY = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJhdWQiOiJsb2NhbGhvc3QiLCJzdWIiOiJ0ZXN0IiwiZXhwIjozMjUwMzY3NjQwMCwiaWF0IjoxNjU1ODgwOTgwLjg2MTcyNywibmJmIjoxNjU1ODgwOTgwLjg2MTczNCwibGljZW5zZS10b2dnbGVzIjp7IjAwMDAwMSI6dHJ1ZSwiMDAwMDAyIjpmYWxzZSwiMDAwMDAzIjp0cnVlfX0.MVCdyweIzi7PamGhliTMiH1Ipt6sT2TmbH4LK6u5iuaTSAE7_TDYe8YZvUClUcAk7XrtLjhNQ2l88cfzlm5w5j5_IqyOF0LWd2RybjBMU-PxCOCRirapL3QMc5rFdr1NV_AysLYENgQhyzLldE4HfnquSRLcOEFEAimGk8TKPKZJe-ET0cmEZG599tnW87rttZr2Zj8WKjGzXAxhGaCvL6a6UTgs15CjSZoL_uGbuAb4rDD1iumpd9vy3s3utrUa2-CG_by8e5uY57n_prWqMQk5Ug64xP7ZML2GeUzciWvUGo6cmu9CT4WY-kLAW4oO0ADiFWwADe91J2I9xaYiqdB-UGqTdFdfNa8rUUXVO8VHG6SRbNBflxBA8ycTryBjPwiIiOtx2L9hNZuDDQTjgmW15rf4P89lceO8WYqSfVDIjffwTWd7tfcUQ9I3hNnY92QmiCXkf-QU_hXb7weAOXsjcfqOt2aQsg_vk8DBwV7PBPKJ6ceESoHehiwN1hCmVMUQKLlury5BhTYt_ZXITDWro8IxP2UgvonaSXtcGWhJWV-QmsbIlaJPB4c5FyAcGK0BoDsDuDW-_XQwUNyY8VuZFRk5N88Hn_Lnzb2iO1MZR69W4g1W9854-pusnjXii3xrWsekAfRw0lQx5wRT-M2vmdzfuvjO-vwMUUKJl04';

    public function testGetInfoWithValidLicenseKey(): void
    {
        $shop = new Shop('my-shop-id', 'http://localhost', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $shop->commercialLicenseKey = self::LICENSE_KEY;

        $commercialLicense = new CommercialLicense($shopRepository);
        $info = $commercialLicense->getInfo('my-shop-id');

        $this->assertInstanceOf(LicenseInfo::class, $info);

        $this->assertEquals('localhost', $info->licenseDomain);
        $this->assertEquals('2022-06-22 06:56:20', $info->issuedAt);
        $this->assertNotEmpty($info->expiresAt);
    }

    public function testGetInfoWithMissingLicenseKey(): void
    {
        $shop = new Shop('my-shop-id', 'http://localhost', 'secret');

        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $shopRepository->createShop($shop);

        $this->expectException(LicenseException::class);
        $this->expectExceptionMessage('License key not found for shop "my-shop-id"');

        $commercialLicense = new CommercialLicense($shopRepository);
        $commercialLicense->getInfo('my-shop-id');
    }

    public function testMissingShop(): void
    {
        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shop with id "my-shop-id" not found');

        $commercialLicense = new CommercialLicense($shopRepository);
        $commercialLicense->getInfo('my-shop-id');
    }

    public function testValidateWithInvalidLicenseKey(): void
    {
        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();

        $this->expectException(LicenseException::class);
        $this->expectExceptionMessage('License key not valid: invalid_license_key');

        $commercialLicense = new CommercialLicense($shopRepository);

        $commercialLicense->validate('https://shop.com', 'invalid_license_key');
    }

    public function testValidateWithInvalidDomain(): void
    {
        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $commercialLicense = new CommercialLicense($shopRepository);

        $this->expectException(LicenseException::class);
        $this->expectExceptionMessage('License domain not valid: invalid.com');

        $commercialLicense->validate('https://invalid.com', self::LICENSE_KEY);
    }

    public function testValidateWithEmptyDomain(): void
    {
        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $commercialLicense = new CommercialLicense($shopRepository);

        $this->expectException(LicenseException::class);
        $this->expectExceptionMessage('License domain not provided');

        $commercialLicense->validate('', self::LICENSE_KEY);
    }

    public function testValidateNotProviderLicenseKey(): void
    {
        /** @var ShopRepositoryInterface<Shop> $shopRepository */
        $shopRepository = new MockShopRepository();
        $commercialLicense = new CommercialLicense($shopRepository);

        $this->expectException(LicenseException::class);
        $this->expectExceptionMessage('License key not provided');

        $commercialLicense->validate('https://shop.com', '');
    }
}
