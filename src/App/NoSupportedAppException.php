<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\App;

class NoSupportedAppException extends \RuntimeException
{
    public static function fromShopwareVersion(string $version): self
    {
        return new self(sprintf('Could not find a supported app for Shopware version "%s"', $version));
    }

    public static function fromAppVersion(string $version): self
    {
        return new self(sprintf('Could not find an app with version "%s"', $version));
    }
}
