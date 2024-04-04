<?php

namespace Shopware\ServiceBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Shopware\App\SDK\Event\RegistrationCompletedEvent;
use Shopware\App\SDK\Event\ShopActivatedEvent;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\Feature\ShopOperation;
use Shopware\ServiceBundle\Listener\ShopActivated;
use Shopware\ServiceBundle\Listener\ShopRegistered;
use Shopware\ServiceBundle\Manifest\ManifestSelector;
use Shopware\ServiceBundle\Message\UpdateShopConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'services:update-shops',
    description: 'Add a short description for your command',
)]
class UpdateShopsCommand extends Command
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly ManagerRegistry $registry,
        private readonly ManifestSelector $manifestSelector,
        private readonly MessageBusInterface $messageBus
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('Updating Shops with latest features');

        foreach ($this->findAll() as $shop) {
            $manifest = $this->manifestSelector->choose($shop->shopVersion);

            if ($shop->manifestHash === null || $manifest->hash() !== $shop->manifestHash) {
                //if the manifest hash is not set, lets send the most applicable manifest
                //if it's set but the corresponding file hash is different, it means it's been updated,
                //so we send the latest version
                $this->messageBus->dispatch(new UpdateShopConfig($shop->getShopId(), $shop->shopVersion));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return iterable<Shop>
     */
    public function findAll(): iterable
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

            $this->registry->getManagerForClass(Shop::class)->clear();
        } while (\count($shops) > 0);
    }
}
