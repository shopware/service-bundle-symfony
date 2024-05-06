<?php

namespace Shopware\ServiceBundle\MessageHandler;

use Shopware\ServiceBundle\Message\UpdateShopManifest;
use Shopware\ServiceBundle\Service\ShopManifest;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdateShopManifestHandler
{
    public function __construct(
        private ShopManifest $shopManifest,
    )
    {
    }

    public function __invoke(UpdateShopManifest $updateShopManifestMessage): void
    {
        $this->shopManifest->update($updateShopManifestMessage->shopId, $updateShopManifestMessage->toVersion);
    }
}