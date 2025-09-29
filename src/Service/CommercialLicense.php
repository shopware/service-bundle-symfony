<?php

namespace Shopware\ServiceBundle\Service;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Shopware\ServiceBundle\Exception\LicenseException;

class CommercialLicense
{
    public function validate(string $licenseKey): LicenseInfo
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

        /** @var array<string, string|int|bool> $toggles */
        $toggles = $token->claims()->get('license-toggles') ?? [];

        /** @var string|null $planName */
        $planName = $token->claims()->get('plan-name') ?? null;
        /** @var string|null $planVariant */
        $planVariant = $token->claims()->get('plan-variant') ?? null;
        /** @var string|null $planUsage */
        $planUsage = $token->claims()->get('plan-usage') ?? null;

        return new LicenseInfo(
            $audience[0],
            $issuedAt,
            $exp,
            $toggles,
            $planName,
            $planVariant,
            $planUsage,
        );
    }

    private static function getStrictlyAtTimeConstraint(): StrictValidAt
    {
        return new StrictValidAt(SystemClock::fromUTC(), new \DateInterval('PT5M'));
    }
}
