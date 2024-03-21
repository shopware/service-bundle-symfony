<?php

namespace Shopware\ServiceBundle\Feature;

class ShopOperation
{
    public function __construct(public ?string $fromVersion = null, public ?string $toVersion)
    {
    }
    
    public static function install(string $version)
    {
        return new self(null, $version);
    }

    public static function update(string $fromVersion, string $toVersion)
    {
        return new self($fromVersio, $toVersion);
    }

    public static function uninstall()
    {
        return new self();
    }
}