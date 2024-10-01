<?php

namespace Shopware\ServiceBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Shopware\ServiceBundle\App\NoSupportedAppException;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Message\ShopUpdated;
use Symfony\Component\Messenger\MessageBusInterface;

class UpdateAllShops
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly AppSelector $appSelector,
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function execute(): void
    {
        foreach ($this->findAll() as $shop) {
            if (!$shop->isShopActive()) {
                continue;
            }

            /** @var string $shopVersion */
            $shopVersion = $shop->shopVersion;

            try {
                $app = $this->appSelector->select($shopVersion);
            } catch (NoSupportedAppException $e) {
                //app was deleted and no new version is available for shopware version
                continue;
            }

            if ($shop->selectedAppHash === null || $app->hash !== $shop->selectedAppHash) {
                //if the app hash is not set, lets send the most applicable app
                //if it's set but the corresponding file hash is different, it means it's been updated,
                //so we send the latest version
                $this->messageBus->dispatch(new ShopUpdated($shop->getShopId(), $shopVersion));
            }
        }
    }

    /**
     * @return iterable<Shop>
     */
    private function findAll(): iterable
    {
        $repository = $this->registry->getRepository(Shop::class);

        $batchCount = 100;

        // current offset to navigate over the entire set
        $offset = 0;

        do {
            /** @var Shop[] $shops */
            $shops = $repository->findBy([], null, $batchCount, $offset);

            yield from $shops;

            $offset += $batchCount;

            $this->registry->getManager()->clear();
        } while (\count($shops) > 0);
    }
}
