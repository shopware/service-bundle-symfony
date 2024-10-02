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
use RuntimeException;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;

class CommercialLicense
{
    /**
     * @param ShopRepositoryInterface<Shop> $shopRepository
     */
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
    ) {}

    /**
     * @throws RuntimeException
     *
     * @return array<string, string>
     */
    public function getInfo(string $shopId): array
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            throw new RuntimeException(sprintf('Shop with id "%s" not found', $shopId));
        }

        $licenseKey = $shop->commercialLicenseKey;

        if (null === $licenseKey) {
            return ['error' => 'No license key found'];
        }

        try {
            $this->validate($shop, $licenseKey);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }

        /** @var UnencryptedToken $parsed */
        $parsed = (new Parser(new JoseEncoder()))->parse($licenseKey);

        /** @var \DateTimeImmutable $issuedAt */
        $issuedAt = $parsed->claims()->get('iat');

        /** @var string[] $audience */
        $audience = $parsed->claims()->get('aud');

        /** @var \DateTimeImmutable $exp */
        $exp = $parsed->claims()->get('exp');

        $days = $exp->diff(new \DateTimeImmutable())->days;

        return [
            'license_domain' => $audience[0],
            'issued_at' => $issuedAt->format('Y-m-d H:i:s'),
            'expires_at' => \sprintf('%s (%d days left)', $exp->format('Y-m-d H:i:s'), $days),
        ];
    }

    public function validate(Shop $shop, string $licenseKey): void
    {
        try {
            $jwt = Configuration::forAsymmetricSigner(
                new Sha512(),
                EmptyKey::create(),
                InMemory::file(__DIR__ . '/public.pem'),
            );

            /** @var UnencryptedToken $token */
            $token = $jwt->parser()->parse($licenseKey);
        } catch (\Throwable) {
            throw new RuntimeException('Invalid license key');
        }

        try {
            $jwt->validator()->assert(
                $token,
                self::getStrictlyAtTimeConstraint(),
            );
        } catch (RequiredConstraintsViolated) {
            throw new RuntimeException('License expired');
        }

        try {
            $shopUrl = parse_url($shop->getShopUrl());
            $domain = $shopUrl['host'] ?? null;
            $jwt->validator()->assert(
                $token,
                new PermittedFor($domain),
            );
        } catch (RequiredConstraintsViolated) {
            throw new RuntimeException('Commercial license host does not match the current host: ' . $domain);
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
