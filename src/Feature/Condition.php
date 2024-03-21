<?php

namespace Shopware\ServiceBundle\Feature;

interface Condition
{
    public function match(ShopOperation $shop): bool;
}