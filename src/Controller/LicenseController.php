<?php

namespace Shopware\ServiceBundle\Controller;

use Shopware\App\SDK\Context\Webhook\WebhookAction;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Exception\LicenseException;
use Shopware\ServiceBundle\Service\CommercialLicense;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class LicenseController
{
    /**
     * @param ShopRepositoryInterface<Shop> $shopRepository
     */
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly CommercialLicense $commercialLicense,
    ) {}

    /**
     * @param Shop $shop
     *
     * This endpoint is used until Shopware version < 6.7.11.
     * Use the service/license/commercial/provided endpoint for Shopware version >= 6.7.11.
     */
    #[Route('/service/license/commercial/sync', name: 'service.sync.commercial-license-info', methods: ['POST'])]
    public function sync(ShopInterface $shop, Request $request): Response
    {
        $payload = $request->getPayload()->all();

        if (!isset($payload['licenseKey']) && !isset($payload['licenseHost'])) {
            return $this->createErrorResponse('missing_license_credentials', 'No licenseKey and licenseHost provided', Response::HTTP_BAD_REQUEST);
        }

        $licenseKey = is_string($payload['licenseKey'] ?? null) ? $payload['licenseKey'] : '';
        $licenseHost = is_string($payload['licenseHost'] ?? null) ? $payload['licenseHost'] : '';

        if ($licenseKey !== '') {
            try {
                $this->commercialLicense->validate($licenseKey);
                $shop->commercialLicenseKey = $licenseKey;
            } catch (LicenseException $e) {
                return $this->createErrorResponse('license_validation_failed', $e->getMessage(), Response::HTTP_FORBIDDEN);
            }
        }

        $shop->commercialLicenseHost = $licenseHost;

        try {
            $this->shopRepository->updateShop($shop);
        } catch (\Throwable $e) {
            return $this->createErrorResponse('shop_update_license_failed', $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    public function provided(WebhookAction $request): Response
    {
        $payload = $request->payload;

        if (!array_key_exists('licenseKey', $payload) && !array_key_exists('licenseHost', $payload)) {
            return $this->createErrorResponse('missing_license_credentials', 'No licenseKey and licenseHost provided', Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('licenseKey', $payload) && !is_string($payload['licenseKey'])) {
            return $this->createErrorResponse('invalid_license_payload', 'licenseKey must be a string', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (array_key_exists('licenseHost', $payload) && !is_string($payload['licenseHost'])) {
            return $this->createErrorResponse('invalid_license_payload', 'licenseHost must be a string', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $licenseKey = $payload['licenseKey'] ?? '';
        $licenseHost = $payload['licenseHost'] ?? '';

        /** @var Shop $shop */
        $shop = $request->shop;

        if ($licenseKey !== '') {
            try {
                $this->commercialLicense->validate($licenseKey);
                $shop->commercialLicenseKey = $licenseKey;
            } catch (LicenseException $e) {
                return $this->createErrorResponse('license_validation_failed', $e->getMessage(), Response::HTTP_FORBIDDEN);
            }
        }

        $shop->commercialLicenseHost = $licenseHost;

        try {
            $this->shopRepository->updateShop($shop);
        } catch (\Throwable $e) {
            return $this->createErrorResponse('shop_update_license_failed', $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function createErrorResponse(string $type, string $detail, int $statusCode): JsonResponse
    {
        $data = [
            'errors' => [
                'type' => $type,
                'detail' => $detail,
            ],
        ];
        return new JsonResponse($data, $statusCode);
    }
}
