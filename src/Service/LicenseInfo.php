<?php

namespace Shopware\ServiceBundle\Service;

use DateTimeImmutable;

class LicenseInfo
{
    public function __construct(public readonly string $licenseDomain, public readonly DateTimeImmutable $issuedAt, public readonly DateTimeImmutable $expiresAt) {}
}
