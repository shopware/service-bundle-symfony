<?php

namespace Shopware\ServiceBundle\Listener;

use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\Event\RegistrationCompletedEvent;
use Shopware\App\SDK\Event\ShopActivatedEvent;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Feature\ShopOperation;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;

#[AsEventListener]
class ShopActivated
{
    public function __construct(
        private FeatureInstructionSet $featureInstructionSet,
        private \Shopware\App\SDK\HttpClient\ClientFactory $shopHttpClientFactory,
        private AppConfiguration $appConfiguration,
    )
    {
    }

    public function __invoke(ShopActivatedEvent $event): void
    {
        /** @var Shop $shop */
        $shop = $event->getShop();

        $payload = $this->featureInstructionSet->getDelta(
            ShopOperation::install($shop->shopVersion)
        );

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $response = $client->patch($shop->getShopUrl() . '/api/services/' .  $this->appConfiguration->getAppName(), $payload);
    }
}