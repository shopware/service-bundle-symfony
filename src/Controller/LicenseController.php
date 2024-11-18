<?php

namespace Shopware\ServiceBundle\Controller;

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
     */
    #[Route('/service/license/commercial/sync', name: 'service.sync.commercial-license-info', methods: ['POST'])]
    public function sync(ShopInterface $shop, Request $request): Response
    {
        $payload = $request->getPayload()->all();

        if (!isset($payload['licenseKey']) && !isset($payload['licenseHost'])) {
            return $this->createErrorResponse('missing_license_credentials', 'No licenseKey and licenseHost provided', Response::HTTP_BAD_REQUEST);
        }

        $licenseKey = $payload['licenseKey'] ?? '';
        $licenseHost = $payload['licenseHost'] ?? '';

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
