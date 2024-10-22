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

        $licenseKey = $payload['licenseKey'] ?? null;

        if (!$licenseKey) {
            $data = [
                'errors' => [
                    'type' => 'missing_license_key',
                    'detail' => 'No license key provided',
                ],
            ];

            return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->commercialLicense->validate($shop->getShopUrl(), $licenseKey);
        } catch (LicenseException $e) {
            $data = [
                'errors' => [
                    'type' => 'license_validation_failed',
                    'detail' => $e->getMessage(),
                ],
            ];

            return new JsonResponse($data, Response::HTTP_FORBIDDEN);
        }

        $shop->commercialLicenseKey = $licenseKey;

        try {
            $this->shopRepository->updateShop($shop);
        } catch (\Throwable) {
            $data = [
                'errors' => [
                    'type' => 'shop_update_license_failed',
                    'detail' => 'Failed to sync commercial license to shop',
                ],
            ];

            return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
