<?php

namespace Shopware\ServiceBundle\MessageHandler;

use Shopware\ServiceBundle\Message\UninstallServiceFromShop;
use Shopware\ServiceBundle\Service\ShopServiceUninstaller;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UninstallServiceFromShopHandler
{
    public function __construct(private ShopServiceUninstaller $shopServiceUninstaller) {}

    public function __invoke(UninstallServiceFromShop $message): void
    {
        $this->shopServiceUninstaller->run($message->shopId);
    }
}
