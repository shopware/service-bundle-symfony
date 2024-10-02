<?php

namespace Shopware\ServiceBundle\Controller;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Shopware\App\SDK\Shop\ShopInterface;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
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
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid payload format'], 400);
        }

        $licenseKey = $payload['licenseKey'] ?? null;

        if (!$licenseKey) {
            return new JsonResponse('License key required', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->commercialLicense->validate($shop, $licenseKey);
        } catch (\Throwable $e) {
            return new JsonResponse('License key not valid: ' . $e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        $shop->commercialLicenseKey = $licenseKey;

        try {
            $this->shopRepository->updateShop($shop);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new JsonResponse(['success' => true]);
    }
}
