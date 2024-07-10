<?php

namespace Shopware\ServiceBundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Entity\Shop;

readonly class ShopUpdater
{
    /**
     * @param ShopRepositoryInterface<Shop> $shopRepository
     */
    public function __construct(
        private ShopRepositoryInterface $shopRepository,
        private AppSelector $appSelector,
        private ClientFactory $shopHttpClientFactory,
        private LoggerInterface $logger,
    ) {}

    public function run(string $shopId, string $toVersion): void
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            throw new \RuntimeException(sprintf('Shop with id %s not found', $shopId));
        }

        $app = $this->appSelector->select($toVersion);


        if (!$this->isNewUpdateAvailable($shop, $app)) {
            $this->logger->debug(sprintf('No new app available for shop %s running Shopware %s - using app version %s', $shop->getShopId(), $shop->shopVersion, $shop->selectedAppVersion));
            return;
        }

        $this->logger->info(sprintf('New version of  app %s for Shopware version %s available', $app->version, $toVersion));

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $response = $client->post($shop->getShopUrl() . '/api/services/trigger-update');

        if ($response->getStatusCode() !== 200) {
            //we should try again
        }
    }

    private function isNewUpdateAvailable(Shop $shop, App $app): bool
    {
        return $shop->selectedAppVersion !== $app->version || $shop->selectedAppHash !== $app->hash;
    }

    public function markShopUpdated(Shop $shop, string $version, string $hash): void
    {
        $shop->shopVersion = $version;
        $shop->selectedAppVersion = $version;
        $shop->selectedAppHash = $hash;

        $this->shopRepository->updateShop($shop);
    }
}
