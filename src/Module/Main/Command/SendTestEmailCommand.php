<?php

declare(strict_types=1);

namespace App\Module\Main\Command;

use App\Module\Main\Service\UserMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-test-email',
    description: 'Sends a test email through the configured mailer.',
)]
final class SendTestEmailCommand extends Command
{
    public function __construct(
        private readonly UserMailer $userMailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Recipient email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        if (!is_string($email)) {
            $io->error('Recipient email address is invalid.');

            return Command::FAILURE;
        }

        $this->userMailer->sendTestEmail($email);

        $io->success(sprintf('Test email sent to %s.', $email));

        return Command::SUCCESS;
    }
}
