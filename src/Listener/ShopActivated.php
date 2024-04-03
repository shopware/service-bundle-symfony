<?php

namespace Shopware\ServiceBundle\Listener;

use Psr\Log\LoggerInterface;
use Shopware\App\SDK\Event\ShopActivatedEvent;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Message\InstallShopConfig;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
readonly class ShopActivated
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(ShopActivatedEvent $event): void
    {
        /** @var Shop $shop */
        $shop = $event->getShop();

        $this->messageBus->dispatch(new InstallShopConfig(
            $shop->getShopId(),
            $shop->shopVersion
        ));
    }
}