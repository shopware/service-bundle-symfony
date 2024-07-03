<?php

namespace Shopware\ServiceBundle\Command;

use Shopware\ServiceBundle\Service\UpdateAllShops;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'services:update-shops',
    description: 'Add a short description for your command',
)]
class UpdateShopsCommand extends Command
{
    public function __construct(private readonly UpdateAllShops $shopUpdater)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('Updating all shops with the latest manifest');

        $this->shopUpdater->execute();

        return Command::SUCCESS;
    }
}
