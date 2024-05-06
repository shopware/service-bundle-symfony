<?php

namespace Shopware\ServiceBundle\Message;

class UpdateShopManifest
{
    public function __construct(public string $shopId, public string $toVersion)
    {
    }
}