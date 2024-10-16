<?php

namespace Shopware\ServiceBundle\Service;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Exception\LicenseException;

class CommercialLicense
{
    /**
     * @param ShopRepositoryInterface<Shop> $shopRepository
     */
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
    ) {}

    public function getInfo(string $shopId): LicenseInfo
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            throw new \RuntimeException(sprintf('Shop with id "%s" not found', $shopId));
        }

        $licenseKey = $shop->commercialLicenseKey;

        if (!$licenseKey) {
            throw LicenseException::licenseNotFound($shopId);
        }

        $this->validate($shop->getShopUrl(), $licenseKey);

        /** @var UnencryptedToken $parsed */
        $parsed = (new Parser(new JoseEncoder()))->parse($licenseKey);

        /** @var \DateTimeImmutable $issuedAt */
        $issuedAt = $parsed->claims()->get('iat');

        /** @var string[] $audience */
        $audience = $parsed->claims()->get('aud');

        /** @var \DateTimeImmutable $exp */
        $exp = $parsed->claims()->get('exp');

        $days = $exp->diff(new \DateTimeImmutable())->days;

        return new LicenseInfo(
            $audience[0],
            $issuedAt->format('Y-m-d H:i:s'),
            \sprintf('%s (%d days left)', $exp->format('Y-m-d H:i:s'), $days),
        );
    }

    public function validate(string $shopUrl, string $licenseKey): void
    {
        if (!$licenseKey) {
            throw LicenseException::licenseNotProvided();
        }

        try {
            $jwt = Configuration::forAsymmetricSigner(
                new Sha512(),
                EmptyKey::create(),
                InMemory::file(__DIR__ . '/public.pem'),
            );

            /** @var UnencryptedToken $token */
            $token = $jwt->parser()->parse($licenseKey);
        } catch (\Throwable) {
            throw LicenseException::licenseInvalid($licenseKey);
        }

        try {
            $jwt->validator()->assert(
                $token,
                self::getStrictlyAtTimeConstraint(),
            );
        } catch (RequiredConstraintsViolated) {
            throw LicenseException::licenseExpired($licenseKey);
        }

        $shopUrl = parse_url($shopUrl);
        $domain = $shopUrl['host'] ?? null;

        if (!$domain) {
            throw LicenseException::domainNotProvided();
        }

        try {
            $jwt->validator()->assert(
                $token,
                new PermittedFor($domain),
            );
        } catch (RequiredConstraintsViolated) {
            throw LicenseException::domainInvalid($domain);
        }

        $jwt->validator()->assert(
            $token,
            new SignedWith($jwt->signer(), $jwt->verificationKey()),
        );
    }

    private static function getStrictlyAtTimeConstraint(): StrictValidAt
    {
        return new StrictValidAt(SystemClock::fromUTC(), new \DateInterval('PT5M'));
    }
}
