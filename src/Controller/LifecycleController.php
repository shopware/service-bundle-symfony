<?php

namespace Shopware\ServiceBundle\Controller;

use Psr\Log\LoggerInterface;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\App\AppZipper;
use Shopware\ServiceBundle\App\NoSupportedAppException;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Message\ShopUpdated;
use Shopware\ServiceBundle\Service\ShopUpdater;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        private readonly AppZipper $appZipper,
    ) {}

    #[Route('/service/lifecycle/select-app', name: 'api.lifecycle.select-app', methods: ['GET'])]
    public function selectApp(#[MapQueryParameter] string $shopwareVersion): Response
    {
        try {
            $app = $this->appSelector->select($shopwareVersion);
        } catch (NoSupportedAppException) {
            $data = [
                'errors' => [
                    'type' => 'unsupported_platform_version',
                    'detail' => sprintf('No supported app version for Shopware platform version "%s"', $shopwareVersion),
                    'available_versions' => array_map(fn(App $app) => $app->version, $this->appSelector->all()),
                ],
            ];

            return new JsonResponse($data, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'app-version' => $app->version,
            'app-revision' => $app->revision,
            'app-hash' => $app->hash,
            'app-zip-url' =>  $this->urlGenerator->generate('shopware_service_lifecycle_app_zip', ['version' => $app->version]),
        ]);
    }

    #[Route('/service/lifecycle/app-zip/{version}', name: 'api.lifecycle.app-zip', methods: ['GET'], requirements: ['version' => '\d/.\d/.\d/.\d'])]
    public function getAppZip(string $version): Response
    {
        try {
            $app = $this->appSelector->specific($version);
        } catch (NoSupportedAppException) {
            $data = [
                'errors' => [
                    'type' => 'unknown_app_version',
                    'detail' => sprintf('App with version "%s" does not exist', $version),
                    'available_versions' => array_map(fn(App $app) => $app->version, $this->appSelector->all()),
                ],
            ];

            return new JsonResponse($data, Response::HTTP_NOT_FOUND);
        }

        $zipContent = $this->appZipper->zip($app);

        $response = new Response($zipContent);
        $response->headers->set('sw-app-version', $app->version);
        $response->headers->set('sw-app-revision', $app->revision);
        $response->headers->set('sw-app-hash', $app->hash);
        $response->headers->set('sw-app-zip-url', $this->urlGenerator->generate('shopware_service_lifecycle_app_zip', ['version' => $app->version]));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $app->name . '.zip"');

        return $response;
    }

    public function reportUpdate(WebhookAction $request): Response
    {
        $newVersion = $request->payload['newVersion'] ?? null;

        if (!is_string($newVersion)) {
            return new Response(null, 422);
        }

        $this->logger->info(sprintf('Reporting update for shop: "%s" to version: "%s"', $request->shop->getShopId(), $newVersion), $request->payload);

        $this->messageBus->dispatch(new ShopUpdated(
            $request->shop->getShopId(),
            $newVersion,
        ));

        return new Response(null, 204);
    }

    public function serviceUpdateFinished(WebhookAction $request): Response
    {
        $version = $request->payload['appVersion'] ?? null;

        if (!is_string($version)) {
            return new Response(null, 422);
        }

        [$version, $hash] = explode('-', $version, 2);

        $this->logger->info(sprintf('Service was updated for shop: "%s"', $request->shop->getShopId()), $request->payload);
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
