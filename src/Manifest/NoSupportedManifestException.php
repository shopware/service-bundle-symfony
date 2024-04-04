<?php declare(strict_types=1);

namespace Shopware\ServiceBundle\Manifest;

class NoSupportedManifestException extends \RuntimeException
{
    public static function fromShopwareVersion(string $version): self
    {
        return new self(sprintf('Could not find a support manifest for Shopware version "%s"', $version)); 
    }
}