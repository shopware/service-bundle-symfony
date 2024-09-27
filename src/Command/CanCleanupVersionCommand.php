<?php

namespace Shopware\ServiceBundle\Command;

use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\ShopRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'services:can-cleanup-version',
    description: 'Checks if any shops are still running on a given version.',
)]
class CanCleanupVersionCommand extends Command
{
    public function __construct(
        private ShopRepository $repository,
        private readonly AppSelector $appSelector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'version',
            InputArgument::REQUIRED,
            'The version to query.',
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
            $io->success(sprintf('No shops registered on version "%s". Version can be safely removed.', $version));
            return Command::SUCCESS;
        }

        $io->error(sprintf('"%d" shops registered on version "%s". Version cannot be removed.', count($shopIds), $version));


        return Command::FAILURE;
    }
}
