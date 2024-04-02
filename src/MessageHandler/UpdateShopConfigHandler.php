<?php

namespace Shopware\ServiceBundle\MessageHandler;

use Shopware\ServiceBundle\Message\UpdateShopConfig;
use Shopware\ServiceBundle\Service\ShopConfig;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdateShopConfigHandler
{
    public function __construct(
        private ShopConfig $shopUpdater,
    )
    {
    }

    public function __invoke(UpdateShopConfig $updateShopConfigMessage): void
    {
        $this->shopUpdater->update($updateShopConfigMessage->shopId, $updateShopConfigMessage->toVersion);
    }
}