<?php

namespace Shopware\ServiceBundle\Command;

use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Message\UninstallServiceFromShop;
use Shopware\ServiceBundle\ShopRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'services:uninstall-version',
    description: 'Attempts to uninstall the service from any shops on the given version.',
)]
class UninstallVersionCommand extends Command
{
    public function __construct(
        private ShopRepository $repository,
        private readonly AppSelector $appSelector,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'version',
            InputArgument::REQUIRED,
            'The version to remove.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $version = $input->getArgument('version');
        assert(is_string($version));

        if (!$this->appSelector->hasVersion($version)) {
            $io->error(sprintf('Version "%s" does not exist', $version));
            return Command::FAILURE;
        }

        $shopIds = [];
        foreach ($this->repository->findActiveShopsForVersion($version) as $shop) {
            $shopIds[] = $shop->getShopId();
        }

        if (empty($shopIds)) {
            $io->info(sprintf('No shops registered on version "%s"', $version));
            return Command::SUCCESS;
        }

        $io->info(sprintf('Attempting to uninstall service version "%s" from "%d" shops', $version, count($shopIds)));
        foreach ($shopIds as $shopId) {
            $this->messageBus->dispatch(new UninstallServiceFromShop($shopId));
        }


        return Command::SUCCESS;
    }
}
