<?php

namespace Shopware\ServiceBundle\Controller;

use Psr\Log\LoggerInterface;
use Shopware\ServiceBundle\Manifest\ManifestSelector;
use Shopware\ServiceBundle\Manifest\NoSupportedManifestException;
use Shopware\ServiceBundle\Message\UpdateShopManifest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class LifecycleController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly ManifestSelector $manifestSelector,
    ) {
    }
    
    public function reportUpdate(WebhookAction $request): Response
    {
        $this->logger->error('Reporting update', $request->payload);

        $this->messageBus->dispatch(new UpdateShopManifest(
            $request->shop->getShopId(),
            $request->payload['new_version'])
        );

        return new Response(null, 204);
    }

    #[Route('/service/lifecycle/install', name: 'api.lifecycle.install', methods: ['GET'])]
    public function install(Request $request): Response
    {
        try {
            $manifest = $this->manifestSelector->select($request->query->get('shopwareVersion'));

            return new Response($manifest->content);
        } catch (NoSupportedManifestException $e) {
            return new Response(status: 404);
        }
    }
}