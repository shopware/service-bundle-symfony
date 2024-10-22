<?php

declare(strict_types=1);

namespace Shopware\ServiceBundle\Test;

use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Test\MockShopRepository;

class MockShopServiceRepository extends MockShopRepository
{
    public function updateShop(ShopInterface $shop): void
    {
        if (!array_key_exists($shop->getShopId(), $this->shops)) {
            throw new \RuntimeException(sprintf('Shop with id "%s" not found', $shop->getShopId()));
        }

        $this->shops[$shop->getShopId()] = $shop;
    }
}
