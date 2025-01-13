<?php

namespace Shopware\ServiceBundle\Service;

use DateTimeImmutable;

class LicenseInfo
{
    /** @param array<string, string|int|bool> $toggles */
    public function __construct(
        public readonly string            $licenseDomain,
        public readonly DateTimeImmutable $issuedAt,
        public readonly DateTimeImmutable $expiresAt,
        public readonly array             $toggles,
    ) {}
}
