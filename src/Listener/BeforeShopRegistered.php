<?php

namespace Shopware\ServiceBundle\Listener;

use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Manifest\ManifestSelector;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Shopware\App\SDK\Event\BeforeRegistrationCompletedEvent;

#[AsEventListener]
class BeforeShopRegistered
{
    public function __construct(private ManifestSelector $manifestSelector)
    {

    }
    
    public function __invoke(BeforeRegistrationCompletedEvent $event): void
    {
        /** @var Shop $shop */
        $shop = $event->getShop();

        $shop->shopVersion = $event->getRequest()->getHeaderLine('sw-version');
        $shop->manifestHash = $this->manifestSelector->select($shop->shopVersion)->hash;
    }
}