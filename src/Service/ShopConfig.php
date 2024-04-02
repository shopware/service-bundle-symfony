<?php

namespace Shopware\ServiceBundle\Service;

use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Shopware\ServiceBundle\Feature\ShopOperation;

readonly class ShopConfig
{
    public function __construct(
        private ShopRepositoryInterface $shopRepository,
        private FeatureInstructionSet $featureInstructionSet,
        private ClientFactory $shopHttpClientFactory,
        private AppConfiguration $appConfiguration
    )
    {
    }

    public function install(string $shopId, string $version): void
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            //throw
        }

        $payload = $this->featureInstructionSet->getDelta(
            ShopOperation::install($version)
        );

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $response = $client->patch($shop->getShopUrl() . '/api/services/' .  $this->appConfiguration->getAppName(), $payload);

        if (!$response->ok()) {

        }
    }

    public function update(string $shopId, string $toVersion): void
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            //throw
        }

        $payload = $this->featureInstructionSet->getDelta(
            ShopOperation::update($shop->shopVersion, $toVersion)
        );

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $response = $client->patch($shop->getShopUrl() . '/api/services/' .  $this->appConfiguration->getAppName(), $payload);

        if ($response->ok()) {
            $shop->shopVersion = $toVersion;
            $this->shopRepository->updateShop($shop);
        }
    }
}