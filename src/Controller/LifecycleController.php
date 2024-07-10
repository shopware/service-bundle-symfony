<?php

namespace Shopware\ServiceBundle\Controller;

use Psr\Log\LoggerInterface;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\App\AppZipper;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Message\ShopUpdated;
use Shopware\ServiceBundle\Service\ShopUpdater;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
class LifecycleController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly AppSelector $appSelector,
        private readonly ShopUpdater $shopUpdater,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('/service/lifecycle/choose-app', name: 'api.lifecycle.choose-app', methods: ['GET'])]
    public function chooseApp(#[MapQueryParameter] string $shopwareVersion): Response
    {
        $app = $this->appSelector->select($shopwareVersion);

        return new JsonResponse([
            'app-version' => $app->version,
            'app-revision' => $app->revision,
            'app-hash' => $app->hash,
            'app-zip-url' =>  $this->urlGenerator->generate('shopware_service_lifecycle_app_zip', ['version' => $app->version]),
        ]);
    }

    #[Route('/service/lifecycle/app-zip/{version}', name: 'api.lifecycle.app-zip', methods: ['GET'], requirements: ['version' => '\d/.\d/.\d/.\d'])]
    public function getAppZip(Request $request, string $version): Response
    {
        $app = $this->appSelector->specific($version);

        $zipContent = (new AppZipper())->zip($app);

        $response = new Response($zipContent);
        $response->headers->set('sw-app-revision', $app->version . '-' . $app->hash);
        $response->headers->set('sw-app-version', $app->version);
        $response->headers->set('sw-app-hash', $app->hash);
        $response->headers->set('sw-app-zip-url', $this->urlGenerator->generate('shopware_service_lifecycle_app_zip', ['version' => $app->version]));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $app->name . '.zip"');

        return $response;
    }

    public function reportUpdate(WebhookAction $request): Response
    {
        $this->logger->info('Reporting update', $request->payload);

        $newVersion = $request->payload['newVersion'];

        if (!is_string($newVersion)) {
            return new Response(null, 422);
        }

        $this->messageBus->dispatch(new ShopUpdated(
            $request->shop->getShopId(),
            $newVersion,
        ));

        return new Response(null, 204);
    }

    public function serviceUpdateFinished(WebhookAction $request): Response
    {
        $this->logger->info('Service was updated in shop', $request->payload);

        $version = $request->payload['appVersion'];

        if (!is_string($version)) {
            return new Response(null, 422);
        }

        [$version, $hash] = explode('-', $version);

        /** @var Shop $shop */
        $shop = $request->shop;

        $this->shopUpdater->markShopUpdated(
            $shop,
            $version,
            $hash,
        );

        return new Response(null, 204);
    }
}
