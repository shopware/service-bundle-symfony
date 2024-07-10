<?php

namespace Shopware\ServiceBundle\Listener;

use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\App\AppSelector;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Shopware\App\SDK\Event\BeforeRegistrationCompletedEvent;

#[AsEventListener]
class BeforeShopRegistered
{
    public function __construct(private AppSelector $appSelector) {}

    public function __invoke(BeforeRegistrationCompletedEvent $event): void
    {
        /** @var Shop $shop */
        $shop = $event->getShop();

        $shop->shopVersion = $event->getRequest()->getHeaderLine('sw-version');

        $selectedApp = $this->appSelector->select($shop->shopVersion);

        $shop->selectedAppVersion = $selectedApp->version;
        $shop->selectedAppHash = $selectedApp->hash;
    }
}
