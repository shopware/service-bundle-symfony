<?php

namespace Shopware\ServiceBundle\Test;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\ShopRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;

#[CoversClass(ShopRepository::class)]
class ShopRepositoryTest extends TestCase
{
    private ManagerRegistry $managerRegistry;
    /**
     * @var ObjectRepository<Shop>&MockObject
     */
    private ObjectRepository&MockObject $repository;
    private EntityManagerInterface&MockObject $entityManager;
    private ShopRepository $shopRepository;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->managerRegistry->method('getRepository')->willReturn($this->repository);
        $this->managerRegistry->method('getManager')->willReturn($this->entityManager);

        $this->shopRepository = new ShopRepository($this->managerRegistry);
    }

    public function testFindAllWithMultipleBatches(): void
    {
        $shop1 = $this->createMock(Shop::class);
        $shop2 = $this->createMock(Shop::class);
        $shop3 = $this->createMock(Shop::class);

        $matcher = $this->exactly(3);
        $this->repository
            ->expects($matcher)
            ->method('findBy')
            ->willReturnCallback(function () use ($matcher, $shop1, $shop2, $shop3) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$shop1, $shop2], // First batch
                    2 => [$shop3],         // Second batch
                    3 => [],               // Third call returns empty
                    default => null,
                };
            });

        $this->entityManager->expects($this->exactly(2))->method('clear');

        $result = iterator_to_array($this->shopRepository->findAll(), false);

        $this->assertCount(3, $result);
        $this->assertSame($shop1, $result[0]);
        $this->assertSame($shop2, $result[1]);
        $this->assertSame($shop3, $result[2]);
    }

    public function testFindActiveShopsForVersion(): void
    {
        $shop1 = $this->createMock(Shop::class);
        $shop2 = $this->createMock(Shop::class);
        $shop3 = $this->createMock(Shop::class);

        $matcher = $this->exactly(3);
        $this->repository
            ->expects($matcher)
            ->method('findBy')
            ->with([
                'shopActive' => true,
                'selectedAppVersion' => '1.0.0',
            ])
            ->willReturnCallback(function () use ($matcher, $shop1, $shop2, $shop3) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$shop1, $shop2], // First batch
                    2 => [$shop3],         // Second batch
                    3 => [],               // Third call returns empty
                    default => null,
                };
            });

        $this->entityManager->expects($this->exactly(2))->method('clear');

        $result = iterator_to_array($this->shopRepository->findActiveShopsForVersion('1.0.0'), false);

        $this->assertCount(3, $result);
        $this->assertSame($shop1, $result[0]);
        $this->assertSame($shop2, $result[1]);
        $this->assertSame($shop3, $result[2]);
    }
}
