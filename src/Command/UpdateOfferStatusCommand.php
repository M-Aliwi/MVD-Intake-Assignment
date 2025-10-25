<?php

namespace App\Command;

use App\Service\OfferService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-offer-status',
    description: 'Update status of pending offers from Moving Digital API',
)]
class UpdateOfferStatusCommand extends Command
{
    public function __construct(
        private readonly OfferService $offerService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Updating offer statuses from Moving Digital API');

        try {
            $pendingOffers = $this->offerService->getPendingOffers();
            
            if (empty($pendingOffers)) {
                $io->success('No pending offers found.');
                return Command::SUCCESS;
            }

            $io->info(sprintf('Found %d pending offers to check.', count($pendingOffers)));

            $updated = 0;
            $errors = 0;

            foreach ($pendingOffers as $offer) {
                $io->text(sprintf('Checking offer ID %d (External ID: %s)...', 
                    $offer->getId(), 
                    $offer->getExternalId() ?? 'N/A'
                ));

                try {
                    $wasUpdated = $this->offerService->updateOfferStatus($offer);
                    
                    if ($wasUpdated) {
                        $updated++;
                        $io->success(sprintf('Updated offer ID %d to status: %s', 
                            $offer->getId(), 
                            $offer->getStatus()
                        ));
                    } else {
                        $io->comment(sprintf('No status change for offer ID %d', $offer->getId()));
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $io->error(sprintf('Error updating offer ID %d: %s', 
                        $offer->getId(), 
                        $e->getMessage()
                    ));
                }
            }

            $io->section('Summary');
            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total pending offers', count($pendingOffers)],
                    ['Successfully updated', $updated],
                    ['Errors', $errors],
                ]
            );

            if ($errors > 0) {
                $io->warning(sprintf('Completed with %d errors.', $errors));
                return Command::FAILURE;
            }

            $io->success('All offer statuses updated successfully.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error(sprintf('Command failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
