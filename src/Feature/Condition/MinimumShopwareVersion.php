<?php

namespace Shopware\ServiceBundle\Feature\Condition;

use App\Feature\Condition;

class MinimumShopwareVersion implements Condition
{
    public function __construct(public string $version)
    {
    }

    public function match(\App\ShopUpdate $shop): bool
    {
        return version_compare($shop->toVersion, $this->version, '>=');
    }
}