<?php

namespace Shopware\ServiceBundle\Listener;

use Psr\Log\LoggerInterface;
use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\Event\RegistrationCompletedEvent;
use Shopware\App\SDK\Event\ShopActivatedEvent;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Feature\ShopOperation;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Shopware\App\SDK\Event\BeforeRegistrationCompletedEvent;

#[AsEventListener]
class BeforeShopRegistered
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function __invoke(BeforeRegistrationCompletedEvent $event): void
    {
        /** @var Shop $shop */
        $shop = $event->getShop();

        $this->logger->alert($event->getRequest()->getHeaderLine('sw-version'));

        $shop->shopVersion = $event->getRequest()->getHeaderLine('sw-version');
    }
}