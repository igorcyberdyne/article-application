<?php

namespace App\Command;

use App\DTO\ArticleDto;
use App\Service\ArticleProviderService;
use App\Service\ArticleService;
use App\Service\ProcessTracker;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'app:load-articles', description: 'Load articles from external source')]
class LoadArticleCommand extends Command
{
    public function __construct(
        private readonly ArticleProviderService $articleProviderService,
        private readonly ArticleService         $articleService,
        private readonly ProcessTracker         $processTracker,
    )
    {
        parent::__construct();
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            $this->getDescription(),
            '============',
            '',
        ]);
        $io = new SymfonyStyle($input, $output);

        $this->processTracker->start(function (LoggerInterface $logger) use ($io, $output) {

            // Load from API
            $logger->info("Loading articles from external source");
            /** @var ArticleDto[] $articlesDtoByRepository */
            $articlesDtoByRepository = [];
            foreach ($this->articleProviderService->getProviders() as $provider) {
                $className = get_class($provider);
                try {
                    $articlesDtoByRepository[$className] = $provider->loadArticles();
                } catch (Exception $exception) {
                    $logger->critical(json_encode(["Error on loading article from classname" => $className, "exception" => $exception->getMessage()]));
                }
            }

            // Save article in DB
            $logger->info("Saving articles in DB");
            $i = 0;
            foreach ($articlesDtoByRepository as $keyClass => $articlesDto) {
                $output->writeln(['--> ' . 'Step ' . ++$i . "/" . count($articlesDtoByRepository) . " : " . $keyClass]);
                $io->progressStart(count($articlesDto));

                foreach ($articlesDto as $articleDto) {
                    $io->progressAdvance();

                    try {
                        $this->articleService->createArticle($articleDto);
                    } catch (Exception $exception) {
                        $logger->critical("Error on creating article: " . json_encode(["articleDto" => $articleDto, "exception" => $exception->getMessage()]));
                    }
                }

                $io->progressFinish();
            }
        }, LoadArticleCommand::class);


        $io->success("Finish {$this->getDescription()}");

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command help create articles from external source.')
        ;
    }
}