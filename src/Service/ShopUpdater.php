<?php

namespace Shopware\ServiceBundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Entity\Shop;

readonly class ShopUpdaterNew
{
    public function __construct(
        private ShopRepositoryInterface $shopRepository,
        private AppSelector             $appSelector,
        private ClientFactory           $shopHttpClientFactory,
        private AppConfiguration        $appConfiguration,
        private LoggerInterface         $logger
    )
    {
    }

    public function run(string $shopId, string $toVersion): void
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            //throw
        }

        $app = $this->appSelector->select($toVersion);

        if (!$this->isNewUpdateAvailable($shop, $app)) {
            $this->logger->debug(sprintf('No new app available for shop %s running Shopware %s - using app version %s', $shop->getShopId(), $shop->shopVersion, $shop->selectedAppVersion));
            return;
        }

        $this->logger->info(sprintf('New version of  app %s for Shopware version %s available', $app->version, $toVersion));

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $client->post(
            sprintf(
                '%s/api/services/trigger-update/%s',
                $shop->getShopUrl(),
                $this->appConfiguration->getAppName()
            ),
        );

        //need to move this somewhere, after the app was updated
//        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
//            $shop->shopVersion = $toVersion;
//            $shop->manifestHash = $manifest->hash;
//            $this->shopRepository->updateShop($shop);
//        }
    }

    private function isNewUpdateAvailable(Shop $shop, App $app): bool
    {
        return $shop->selectedAppVersion !== $app->version || $shop->selectedAppHash !== $app->hash;
    }
}