<?php

namespace Shopware\ServiceBundle\Service;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Shopware\ServiceBundle\Exception\LicenseException;

class CommercialLicense
{
    public function validate(string $shopUrl, string $licenseKey): LicenseInfo
    {
        if (!$licenseKey) {
            throw LicenseException::licenseNotProvided();
        }

        $shopUrl = parse_url($shopUrl);
        $domain = $shopUrl['host'] ?? null;

        if (!$domain) {
            throw LicenseException::domainNotProvided();
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

        /** @var \DateTimeImmutable $issuedAt */
        $issuedAt = $token->claims()->get('iat');

        /** @var string[] $audience */
        $audience = $token->claims()->get('aud');

        /** @var \DateTimeImmutable $exp */
        $exp = $token->claims()->get('exp');

        return new LicenseInfo(
            $audience[0],
            $issuedAt,
            $exp,
        );
    }

    private static function getStrictlyAtTimeConstraint(): StrictValidAt
    {
        return new StrictValidAt(SystemClock::fromUTC(), new \DateInterval('PT5M'));
    }
}