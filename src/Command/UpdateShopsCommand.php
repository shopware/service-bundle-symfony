<?php

namespace Shopware\ServiceBundle\Command;

use Shopware\App\SDK\Event\RegistrationCompletedEvent;
use Shopware\App\SDK\Event\ShopActivatedEvent;
use Shopware\App\SDK\Shop\ShopRepositoryInterface;
use Shopware\ServiceBundle\Feature\ShopOperation;
use Shopware\ServiceBundle\Listener\ShopActivated;
use Shopware\ServiceBundle\Listener\ShopRegistered;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Shopware\ServiceBundle\Feature\FeatureInstructionSet;

#[AsCommand(
    name: 'services:update-shops',
    description: 'Add a short description for your command',
)]
class UpdateShopsCommand extends Command
{
    public function __construct(
        private readonly ShopRepositoryInterface $repository,
        private readonly ShopActivated           $shopActivated,
        private readonly ShopRepositoryInterface $shopRepository,
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

        //if there are any new features, push them out to all shops

        return Command::SUCCESS;
    }
}
