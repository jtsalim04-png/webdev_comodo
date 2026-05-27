<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-test-email',
    description: 'Send a test email using the configured mailer.',
)]
class SendTestEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%env(default:default_mailer_from:MAILER_FROM_ADDRESS)%')]
        private string $mailerFromAddress,
        #[Autowire('%env(default:default_mailer_from_name:MAILER_FROM_NAME)%')]
        private string $mailerFromName,
        #[Autowire('%env(default::string:DEV_TEST_EMAIL)%')]
        private string $defaultTestRecipient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::OPTIONAL, 'Recipient email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = $input->getArgument('to') ?: ($this->defaultTestRecipient !== '' ? $this->defaultTestRecipient : 'user123@gmail.com');

        $email = (new Email())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to($to)
            ->subject('Test email from ComodoWebsite')
            ->text('This is a test email sent from the application to verify mailer configuration.')
            ->html('<p>This is a <strong>test email</strong> sent from the application to verify mailer configuration.</p>');

        try {
            $this->mailer->send($email);
            $output->writeln(sprintf('OK: test email sent to %s', $to));

            return Command::SUCCESS;
        } catch (TransportExceptionInterface $e) {
            $output->writeln('<error>Failed to send test email:</error> '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
