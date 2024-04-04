<?php

namespace Shopware\ServiceBundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Shopware\ServiceBundle\Feature\ShopOperation;
use Shopware\ServiceBundle\Manifest\ManifestSelector;

readonly class ShopConfig
{
    public function __construct(
        private ShopRepositoryInterface $shopRepository,
        private ManifestSelector $manifestSelector,
        private ClientFactory $shopHttpClientFactory,
        private AppConfiguration $appConfiguration,
        private LoggerInterface $logger
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
        
        $manifest = $this->manifestSelector->choose($version);
        $this->logger->info(sprintf('Selecting manifest %s for Shopware version %s', $manifest->version, $version));

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $response = $client->patch(
            $shop->getShopUrl() . '/api/services/' .  $this->appConfiguration->getAppName() . '/manifest',
            [
                'manifest' => $manifest->getContent()
            ]
        );

        if ($response->ok()) {
            $shop->manifestHash = $manifest->hash();
            $this->shopRepository->updateShop($shop);
        }
    }

    public function update(string $shopId, string $toVersion): void
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            //throw
        }

        $manifest = $this->manifestSelector->choose($toVersion);
        $this->logger->info(sprintf('Selecting manifest %s for Shopware version %s', $manifest->version, $toVersion));

        $payload = $manifest->getContent();

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $response = $client->patch(
            $shop->getShopUrl() . '/api/services/' .  $this->appConfiguration->getAppName() . '/manifest',
            [
                'manifest' => $manifest->getContent()
            ]
        );

        if ($response->ok()) {
            $shop->shopVersion = $toVersion;
            $shop->manifestHash = $manifest->hash();
            $this->shopRepository->updateShop($shop);
        }
    }
}