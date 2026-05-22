<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendTestEmailCommand extends Command
{
    protected static $defaultName = 'app:send-test-email';
    protected static $defaultDescription = 'Send a test email using the configured mailer.';

    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::OPTIONAL, 'Recipient email address', getenv('DEV_TEST_EMAIL') ?: 'user123@gmail.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = $input->getArgument('to');
        $from = getenv('MAILER_FROM') ?: 'noreply@localhost';

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject('Test email from ComodoWebsite')
            ->text("This is a test email sent from the local application to verify SMTP configuration.")
            ->html('<p>This is a <strong>test email</strong> sent from the local application to verify SMTP configuration.</p>');

        try {
            $this->mailer->send($email);
            $output->writeln(sprintf('OK: test email sent to %s', $to));
            return Command::SUCCESS;
        } catch (TransportExceptionInterface $e) {
            $output->writeln('<error>Failed to send test email:</error> ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
