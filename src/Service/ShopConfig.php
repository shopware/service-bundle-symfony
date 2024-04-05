<?php

namespace Shopware\ServiceBundle\Service;

use Http\Discovery\Psr17Factory;
use Psr\Log\LoggerInterface;
use Shopware\App\SDK\AppConfiguration;
use Shopware\App\SDK\HttpClient\ClientFactory;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
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

    public function update(string $shopId, string $toVersion): void
    {
        /** @var Shop|null $shop */
        $shop = $this->shopRepository->getShopFromId($shopId);

        if (null === $shop) {
            //throw
        }

        $manifest = $this->manifestSelector->select($toVersion);
        $this->logger->info(sprintf('Selecting manifest %s for Shopware version %s', $manifest->version, $toVersion));

        $client = $this->shopHttpClientFactory->createClient($shop);

        $factory = new Psr17Factory();
        $request = $factory->createRequest(
            'PATCH',
            $shop->getShopUrl() . '/api/services/' .  $this->appConfiguration->getAppName() . '/manifest'
        );

        $request = $request
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/xml')
            ->withBody($factory->createStream($manifest->getContent()));

        $response = $client->sendRequest($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $shop->shopVersion = $toVersion;
            $shop->manifestHash = $manifest->hash();
            $this->shopRepository->updateShop($shop);
        }
    }
}