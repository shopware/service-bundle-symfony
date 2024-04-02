<?php

namespace Shopware\ServiceBundle\Message;

class UpdateShopConfig
{
    public function __construct(public string $shopId, public string $toVersion)
    {
    }
}