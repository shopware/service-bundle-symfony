<?php

namespace Shopware\ServiceBundle\Service;

class LicenseInfo
{
    public function __construct(public readonly string $licenseDomain, public readonly string $issuedAt, public readonly string $expiresAt) {}
}
