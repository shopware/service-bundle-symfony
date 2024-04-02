<?php

namespace Shopware\ServiceBundle\Message;

class InstallShopConfig
{
    public function __construct(public string $shopId, public string $version)
    {
    }
}