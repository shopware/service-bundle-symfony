<?php

namespace Shopware\ServiceBundle\Message;

class ShopUpdated
{
    public function __construct(public string $shopId, public string $toVersion) {}
}
