<?php

namespace App\Command;

use App\DataFixtures\EventFixture;
use App\DataFixtures\UserFixture;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:fixtures:export-from-database',
    description: 'Export current database rows into per-entity fixture classes under src/DataFixtures',
)]
class ExportDatabaseFixturesCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $projectDir,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = $this->projectDir.'/src/DataFixtures';

        $users = $this->fetchTable('user');
        $events = $this->fetchTable('event');
        $tickets = $this->fetchTable('ticket');
        $logs = $this->fetchTable('activity_log');

        if ($users !== []) {
            $this->filesystem->dumpFile($dir.'/UserFixture.php', $this->buildUserFixture($users));
        }
        if ($events !== []) {
            $this->filesystem->dumpFile($dir.'/EventFixture.php', $this->buildEventFixture($events));
        }
        $this->filesystem->dumpFile($dir.'/TicketFixture.php', $this->buildTicketFixture($tickets));
        $this->filesystem->dumpFile($dir.'/ActivityLogFixture.php', $this->buildActivityLogFixture($logs));

        $io->success(sprintf(
            'Exported fixtures: %d users, %d events, %d tickets, %d activity logs.',
            \count($users),
            \count($events),
            \count($tickets),
            \count($logs),
        ));

        if ($users === [] && $events === [] && $tickets === [] && $logs === []) {
            $io->warning('Database tables are empty. TicketFixture and ActivityLogFixture were updated; UserFixture/EventFixture were left unchanged (use seed data or add rows first).');
            $this->filesystem->dumpFile($dir.'/TicketFixture.php', $this->buildTicketFixture([]));
            $this->filesystem->dumpFile($dir.'/ActivityLogFixture.php', $this->buildActivityLogFixture([]));
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchTable(string $table): array
    {
        try {
            return $this->connection->fetchAllAssociative(sprintf('SELECT * FROM `%s` ORDER BY id', $table));
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function buildUserFixture(array $rows): string
    {
        $body = '';
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $body .= $this->indent(2, <<<PHP

            \$user = new User();
            \$user->setEmail({$this->exportValue($row['email'])});
            \$user->setRole({$this->exportValue($row['role'])});
            \$user->setFirstName({$this->exportValue($row['first_name'])});
            \$user->setLastName({$this->exportValue($row['last_name'])});
            \$user->setPassword({$this->exportValue($row['password'])});
            \$user->setCreatedAt(new \\DateTimeImmutable({$this->exportValue($row['created_at'])}));
            \$user->setIsActive((bool) {$row['is_active']});
            \$user->setIsVerified((bool) {$row['is_verified']});
            \$user->setVerificationToken({$this->exportNullable($row['verification_token'] ?? null)});
            \$manager->persist(\$user);
            \$this->addReference('user_{$id}', \$user);

            PHP);
        }

        if ($body === '') {
            $body = "\n        // No users in database at export time.\n";
        }

        return $this->wrapFixture(
            'UserFixture',
            'User',
            'App\\Entity\\User',
            false,
            $body,
            \count($rows),
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function buildEventFixture(array $rows): string
    {
        $body = '';
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $organizerId = (int) $row['organizer_id'];
            $createdBy = $row['created_by_id'] ?? null;
            $createdByLine = $createdBy !== null
                ? "\$event->setCreatedBy(\$this->getReference('user_{$createdBy}', User::class));"
                : "\$event->setCreatedBy(null);";

            $body .= $this->indent(2, <<<PHP

            \$event = new Event();
            \$event->setTitle({$this->exportValue($row['title'])});
            \$event->setDescription({$this->exportNullable($row['description'] ?? null)});
            \$event->setEventDate(new \\DateTime({$this->exportValue($row['event_date'])}));
            \$event->setLocation({$this->exportNullable($row['location'] ?? null)});
            \$event->setPrice((float) {$row['price']});
            \$event->setSeatType({$this->exportNullable($row['seat_type'] ?? null)});
            \$event->setOrganizer(\$this->getReference('user_{$organizerId}', User::class));
            {$createdByLine}
            \$event->setCreatedAt(new \\DateTime({$this->exportValue($row['created_at'])}));
            \$manager->persist(\$event);
            \$this->addReference('event_{$id}', \$event);

            PHP);
        }

        if ($body === '') {
            $body = "\n        // No events in database at export time.\n";
        }

        return $this->wrapFixture(
            'EventFixture',
            'Event',
            'App\\Entity\\Event',
            true,
            $body,
            \count($rows),
            [UserFixture::class], // App\DataFixtures\UserFixture
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function buildTicketFixture(array $rows): string
    {
        $body = '';
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $eventId = (int) $row['event_id'];
            $customerId = (int) $row['customer_id'];

            $body .= $this->indent(2, <<<PHP

            \$ticket = new Ticket();
            \$ticket->setEvent(\$this->getReference('event_{$eventId}', Event::class));
            \$ticket->setCustomer(\$this->getReference('user_{$customerId}', User::class));
            \$ticket->setPrice({$this->exportValue((string) $row['price'])});
            \$ticket->setPurchaseDate(new \\DateTime({$this->exportValue($row['purchase_date'])}));
            \$ticket->setQrCodePath({$this->exportNullable($row['qr_code_path'] ?? null)});
            \$ticket->setStatus({$this->exportValue($row['status'])});
            \$ticket->setCreatedAt(new \\DateTimeImmutable({$this->exportValue($row['created_at'])}));
            \$manager->persist(\$ticket);
            \$this->addReference('ticket_{$id}', \$ticket);

            PHP);
        }

        if ($body === '') {
            $body = "\n        // No tickets in database at export time.\n";
        }

        return $this->wrapFixture(
            'TicketFixture',
            'Ticket',
            'App\\Entity\\Ticket',
            true,
            $body,
            \count($rows),
            [UserFixture::class, EventFixture::class], // App\DataFixtures\*
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function buildActivityLogFixture(array $rows): string
    {
        $body = '';
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $userId = $row['user_id'] ?? null;
            $userLine = $userId !== null
                ? "\$log->setUser(\$this->getReference('user_{$userId}', User::class));"
                : "\$log->setUser(null);";

            $body .= $this->indent(2, <<<PHP

            \$log = new ActivityLog();
            {$userLine}
            \$log->setRole({$this->exportValue($row['role'])});
            \$log->setAction({$this->exportValue($row['action'])});
            \$log->setDescription({$this->exportNullable($row['description'] ?? null)});
            \$log->setUsername({$this->exportNullable($row['username'] ?? null)});
            \$log->setTargetData({$this->exportNullable($row['target_data'] ?? null)});
            \$log->setCreatedAt(new \\DateTimeImmutable({$this->exportValue($row['created_at'])}));
            \$manager->persist(\$log);
            \$this->addReference('activity_log_{$id}', \$log);

            PHP);
        }

        if ($body === '') {
            $body = "\n        // No activity logs in database at export time.\n";
        }

        return $this->wrapFixture(
            'ActivityLogFixture',
            'ActivityLog',
            'App\\Entity\\ActivityLog',
            true,
            $body,
            \count($rows),
            [UserFixture::class],
        );
    }

    /**
     * @param list<class-string> $dependencies
     */
    private function wrapFixture(
        string $class,
        string $entityShort,
        string $entityFqcn,
        bool $dependent,
        string $body,
        int $rowCount,
        array $dependencies = [],
    ): string {
        $generatedAt = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $uses = "use {$entityFqcn};\nuse Doctrine\\Bundle\\FixturesBundle\\Fixture;\nuse Doctrine\\Common\\DataFixtures\\DependentFixtureInterface;\nuse Doctrine\\Persistence\\ObjectManager;";

        $depsInterface = $dependent ? ' implements DependentFixtureInterface' : '';
        $depsMethod = '';
        if ($dependent && $dependencies !== []) {
            $depList = implode(', ', array_map(
                static fn (string $class) => '\\'.ltrim($class, '\\').'::class',
                $dependencies,
            ));
            $depsMethod = <<<PHP


                public function getDependencies(): array
                {
                    return [{$depList}];
                }
            PHP;
        }

        return <<<PHP
            <?php

            namespace App\DataFixtures;

            {$uses}

            /**
             * Auto-generated from database ({$rowCount} row(s)) on {$generatedAt}.
             * Regenerate: php bin/console app:fixtures:export-from-database
             */
            class {$class} extends Fixture{$depsInterface}
            {
                public function load(ObjectManager \$manager): void
                {
            {$body}
                    \$manager->flush();
                }
            {$depsMethod}
            }

            PHP;
    }

    private function exportValue(mixed $value): string
    {
        return var_export((string) $value, true);
    }

    private function exportNullable(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'null';
        }

        return $this->exportValue($value);
    }

    private function indent(int $spaces, string $text): string
    {
        $pad = str_repeat(' ', $spaces);

        return implode("\n", array_map(
            static fn (string $line) => $pad.$line,
            explode("\n", trim($text, "\n")),
        ));
    }
}
