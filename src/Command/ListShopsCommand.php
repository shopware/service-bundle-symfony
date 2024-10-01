<?php

namespace Shopware\ServiceBundle\Command;

use Shopware\ServiceBundle\App\AppSelector;
use Shopware\ServiceBundle\Entity\Shop;
use Shopware\ServiceBundle\ShopRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'services:shops:list',
    description: 'List registered shops with their Shopware and app versions.',
)]
class ListShopsCommand extends Command
{
    public function __construct(
        private ShopRepository $repository,
        private AppSelector $appSelector,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption('full', 'f', InputOption::VALUE_NONE, 'Show full hashes');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ConsoleOutput $output */
        $section = $output->section();
        $io = new SymfonyStyle($input, $section);
        $table = new Table($section);
        $table->setHeaders(['Shop ID', 'Shop URL', 'Shopware Version', 'App Version', 'App Hash', 'Latest version?', 'Latest revision?']);

        $this->chunkShops($this->repository->findAll(), function (int $page, array $shops, bool $hasNext) use ($table, $section, $input, $output, $io) {
            $section->clear();

            $io->title('Registered shops ' . $page * 5 . ' - ' . (($page * 5) + count($shops)));


            $table->setRows(
                array_map(
                    function (Shop $shop) use ($input) {

                        $app = $shop->shopVersion ? $this->appSelector->select($shop->shopVersion) : null;

                        $hash = '';
                        if ($shop->selectedAppHash) {
                            $hash = $input->getOption('full') ? $shop->selectedAppHash : substr($shop->selectedAppHash, 0, 10) . '...';
                        }

                        return [
                            $shop->getShopId(),
                            $shop->getShopUrl(),
                            $shop->shopVersion,
                            $shop->selectedAppVersion,
                            $hash,
                            $app && $app->version === $shop->selectedAppVersion ? '✅' : '❌',
                            $app && $app->hash === $shop->selectedAppHash ? '✅' : '❌',
                        ];
                    },
                    $shops,
                ),
            );
            $table->render();

            if ($hasNext) {
                /** @var QuestionHelper $helper */
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Show next batch? ', true);

                return $helper->ask($input, $output, $question);
            }

            return false;
        }, 5);

        return Command::SUCCESS;
    }

    private function chunkShops(\Generator $generator, callable $callback, int $chunkSize = 10): void
    {
        $page = 0;
        $shops = [];
        foreach ($generator as $shop) {
            $shops[] = $shop;

            if (count($shops) === $chunkSize) {
                $generator->next();
                $next = $generator->current();

                $result = $callback($page++, $shops, $next !== null);

                if ($result === false || $next === null) {
                    return;
                }
                $shops = [$next];
            }
        }

        if (!empty($shops)) {
            $callback($page, $shops, false);
        }
    }
}
