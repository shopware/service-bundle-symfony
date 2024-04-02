<?php

namespace Shopware\ServiceBundle\Controller;

use Psr\Log\LoggerInterface;
use Shopware\ServiceBundle\Message\UpdateShopConfig;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsController]
class LifecycleController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
    ) {
    }
    
    public function reportUpdate(WebhookAction $request): Response
    {
        $this->logger->error('Reporting update', $request->payload);

        $this->messageBus->dispatch(new UpdateShopConfig(
            $request->shop->getShopId(),
            $request->payload['new_version'])
        );

        return new Response(null, 204);
    }
}