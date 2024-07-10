<?php

namespace Shopware\ServiceBundle\MessageHandler;

use Shopware\ServiceBundle\Message\ShopUpdated;
use Shopware\ServiceBundle\Service\ShopUpdater;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ShopUpdatedHandler
{
    public function __construct(private ShopUpdater $shopUpdater) {}

    public function __invoke(ShopUpdated $updateShopManifestMessage): void
    {
        $this->shopUpdater->run($updateShopManifestMessage->shopId, $updateShopManifestMessage->toVersion);
    }
}
