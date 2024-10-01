<?php

namespace Shopware\ServiceBundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;

class ShopServiceUninstaller
{
    /**
     * @param ShopRepositoryInterface<Shop> $shopRepository
     */
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly AppConfiguration $serviceConfiguration,
        private readonly ClientFactory $shopHttpClientFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function run(string $shopId): void
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            throw new \RuntimeException(sprintf('Shop with id "%s" not found', $shopId));
        }

        $this->logger->info(sprintf('Uninstalling service for shop "%s"', $shop->getShopId()));

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $result = $client->post($shop->getShopUrl() . '/api/service/uninstall/' . $this->serviceConfiguration->getAppName(), []);

        if ($result->ok()) {
            $shop->setShopActive(false);
            $shop->selectedAppVersion = null;
            $shop->selectedAppHash = null;
            $this->shopRepository->updateShop($shop);
        }
    }
}
