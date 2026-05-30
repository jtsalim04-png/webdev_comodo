<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Service\FixtureLoadState;
use App\Service\RealtimeVersionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class EntityUpdateSubscriber
{
    private array $ignoredEntities = [
        ActivityLog::class,
    ];

    /** @var list<array<string, mixed>> */
    private array $pendingLogRows = [];
    private bool $pendingRealtimeBump = false;

    public function __construct(
        private Security $security,
        private Connection $connection,
        private RealtimeVersionService $realtimeVersionService,
        private FixtureLoadState $fixtureLoadState,
    ) {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleEntityChange($args, 'CREATE', 'Created');
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityClass = get_class($entity);

        if (in_array($entityClass, $this->ignoredEntities, true)) {
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        if (empty($changeSet) || (count($changeSet) === 1 && isset($changeSet['lastLogin']))) {
            return;
        }

        $this->handleEntityChange($args, 'UPDATE', 'Updated');
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->handleEntityChange($args, 'DELETE', 'Deleted');
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->fixtureLoadState->isLoading()) {
            $this->pendingLogRows = [];
            $this->pendingRealtimeBump = false;

            return;
        }

        if ($this->pendingRealtimeBump) {
            $this->realtimeVersionService->bump();
            $this->pendingRealtimeBump = false;
        }

        if ($this->pendingLogRows === []) {
            return;
        }

        $rows = $this->pendingLogRows;
        $this->pendingLogRows = [];

        foreach ($rows as $row) {
            $this->connection->insert('activity_log', $row);
        }
    }

    private function handleEntityChange(LifecycleEventArgs $args, string $action, string $verb): void
    {
        if ($this->fixtureLoadState->isLoading()) {
            return;
        }

        $entity = $args->getObject();
        $entityClass = get_class($entity);

        if (in_array($entityClass, $this->ignoredEntities, true)) {
            return;
        }

        $this->pendingRealtimeBump = true;
        $this->queueLogRow($action, $entity, $verb.' '.$this->getEntityName($entityClass));
    }

    private function queueLogRow(string $action, object $entity, string $description): void
    {
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;
        $user = $this->security->getUser();
        $role = 'SYSTEM';
        $username = 'SYSTEM';
        $userId = null;

        if ($user instanceof User) {
            $userId = $user->getId();
            $username = $user->getEmail();
            $role = $user->getRoles()[0] ?? 'ROLE_USER';
        }

        $this->pendingLogRows[] = [
            'user_id' => $userId,
            'role' => $role,
            'action' => $action,
            'description' => $this->getDetailedDescription($action, $entity, $description, $entityId),
            'username' => $username,
            'target_data' => $this->getTargetData($entity, $entityId),
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    private function getTargetData(object $entity, ?int $entityId): string
    {
        if ($entity instanceof User) {
            $name = $entity->getFirstName().' '.$entity->getLastName();
            $email = $entity->getEmail();

            return "User: {$name} ({$email}) (ID: {$entityId})";
        }

        if ($entity instanceof \App\Entity\Event) {
            return 'Event: '.$entity->getTitle()." (ID: {$entityId})";
        }

        if ($entity instanceof \App\Entity\Ticket) {
            $eventTitle = $entity->getEvent()?->getTitle() ?? 'Unknown Event';
            $customer = $entity->getCustomer();
            $customerName = $customer ? $customer->getFirstName().' '.$customer->getLastName() : 'Unknown';

            return "Ticket: Event: {$eventTitle}, Customer: {$customerName} (ID: {$entityId})";
        }

        return $this->getEntityName(get_class($entity))." (ID: {$entityId})";
    }

    private function getDetailedDescription(string $action, object $entity, string $baseDescription, ?int $entityId): string
    {
        $description = $baseDescription;

        if ($entity instanceof User) {
            $description .= sprintf(' (%s %s, %s, %s)', $entity->getFirstName(), $entity->getLastName(), $entity->getEmail(), $entity->getRole());
        } elseif ($entity instanceof \App\Entity\Event) {
            $description .= ' ('.$entity->getTitle().')';
        } elseif ($entity instanceof \App\Entity\Ticket) {
            $eventTitle = $entity->getEvent()?->getTitle() ?? 'Unknown Event';
            $customer = $entity->getCustomer();
            $customerName = $customer ? $customer->getFirstName().' '.$customer->getLastName() : 'Unknown';
            $description .= " (Event: {$eventTitle}, Customer: {$customerName}, Status: {$entity->getStatus()})";
        }

        if ($entityId) {
            $description .= " #{$entityId}";
        }

        return $description;
    }

    private function getEntityName(string $entityClass): string
    {
        $parts = explode('\\', $entityClass);
        $shortName = end($parts);

        return preg_replace('/(?<!\ )[A-Z]/', ' $0', $shortName);
    }
}
