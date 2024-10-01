<?php

namespace Shopware\ServiceBundle;

use Doctrine\Persistence\ManagerRegistry;
use Generator;
use Shopware\ServiceBundle\Entity\Shop;

class ShopRepository
{
    public function __construct(private readonly ManagerRegistry $registry) {}

    /**
     * @return Generator<Shop>
     */
    public function findAll(): Generator
    {
        return $this->iterate();
    }

    /**
     * @param string $version
     * @return Generator<Shop>
     */
    public function findActiveShopsForVersion(string $version): Generator
    {
        return $this->iterate([
            'shopActive' => true,
            'selectedAppVersion' => $version,
        ]);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return Generator<Shop>
     */
    private function iterate(array $criteria = []): Generator
    {
        $repository = $this->registry->getRepository(Shop::class);

        $batchCount = 100;

        // current offset to navigate over the entire set
        $offset = 0;

        while ($shops = $repository->findBy($criteria, null, $batchCount, $offset)) {
            yield from $shops;

            $this->registry->getManager()->clear();
            $offset += $batchCount;
        }
    }
}
