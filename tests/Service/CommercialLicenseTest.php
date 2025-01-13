<?php

namespace Shopware\ServiceBundle\Test\Service;

use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testValidateWithValidLicenseKey(): void
    {
        $commercialLicense = new CommercialLicense();
        $info = $commercialLicense->validate(self::LICENSE_KEY);

        $this->assertInstanceOf(LicenseInfo::class, $info);

        $this->assertSame('localhost', $info->licenseDomain);

        $expected = (new \DateTimeImmutable("2022-06-22 06:56:20"))->format('Y-m-d H:i:s');
        $this->assertSame($expected, $info->issuedAt->format('Y-m-d H:i:s'));

        $expected = (new \DateTimeImmutable("2999-12-31 23:00:00"))->format('Y-m-d H:i:s');
        $this->assertSame($expected, $info->expiresAt->format('Y-m-d H:i:s'));

        $this->assertSame([
            '000001' => true,
            '000002' => false,
            '000003' => true,
        ], $info->toggles);
    }

    public function testValidateWithInvalidLicenseKey(): void
    {
        $this->expectException(LicenseException::class);
        $this->expectExceptionMessage('License key not valid: invalid_license_key');

        $commercialLicense = new CommercialLicense();

        $commercialLicense->validate('invalid_license_key');
    }

    public function testValidateNotProviderLicenseKey(): void
    {
        $commercialLicense = new CommercialLicense();

        $this->expectException(LicenseException::class);
        $this->expectExceptionMessage('License key not provided');

        $commercialLicense->validate('');
    }
}
