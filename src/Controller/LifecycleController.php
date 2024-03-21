<?php

namespace Shopware\ServiceBundle\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\App\SDK\Exception\ShopNotFoundException;
use Shopware\App\SDK\Registration\RegistrationService;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\App\SDK\Shop\ShopResolver;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Shopware\ServiceBundle\Feature\ShopOperation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Shopware\App\SDK\Context\Webhook\WebhookAction;

#[AsController]
class LifecycleController
{
    public function __construct(
        private readonly ShopResolver $shopResolver,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly LoggerInterface $logger,
        private readonly FeatureInstructionSet $featureInstructionSet,
        private \Shopware\App\SDK\HttpClient\ClientFactory $shopHttpClientFactory,
        private \Shopware\App\SDK\AppConfiguration $appConfiguration
    ) {
    }
    
    public function reportUpdate(WebhookAction $request): ResponseInterface
    {
        $this->logger->error('Reporting update', $request->payload);

        $newVersion = $request->payload['new_version'];
        /** @var Shop $shop */
        $shop = $request->shop;

        $delta = $this->featureInstructionSet->getDelta(
            ShopOperation::update($shop->shopVersion, $newVersion)
        );

        $client = $this->shopHttpClientFactory->createSimpleClient($shop);

        $response = $client->patch($shop->getShopUrl() . '/api/services/' .  $this->appConfiguration->getAppName(), $payload);

        return new Response(null, 204);
    }
}