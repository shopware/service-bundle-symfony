<?php

namespace Shopware\ServiceBundle\MessageHandler;

use Shopware\ServiceBundle\Message\InstallShopConfig;
use Shopware\ServiceBundle\Service\ShopConfig;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class InstallShopConfigHandler
{
    public function __construct(
        private ShopConfig $shopUpdater,
    )
    {
    }

    public function __invoke(InstallShopConfig $updateShopConfigMessage): void
    {
        $this->shopUpdater->install($updateShopConfigMessage->shopId, $updateShopConfigMessage->version);
    }
}