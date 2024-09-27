<?php

namespace Shopware\ServiceBundle\Message;

class UninstallServiceFromShop
{
    public function __construct(public string $shopId) {}
}
