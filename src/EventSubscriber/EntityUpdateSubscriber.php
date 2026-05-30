<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Service\RealtimeVersionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class EntityUpdateSubscriber implements EventSubscriber
{
    private array $ignoredEntities = [
        ActivityLog::class, // Don't log our own logs
    ];

    private array $pendingLogs = [];
    private bool $isFlushingLogs = false;

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private RealtimeVersionService $realtimeVersionService,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist => 'postPersist',
            Events::postUpdate => 'postUpdate',
            Events::postRemove => 'postRemove',
            Events::postFlush => 'postFlush',
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityClass = get_class($entity);
        
        if (in_array($entityClass, $this->ignoredEntities)) {
            return;
        }

        error_log("EntityUpdateSubscriber: postPersist called for " . $entityClass);
        $this->logAction('CREATE', $entity, 'Created ' . $this->getEntityName($entityClass));
        $this->realtimeVersionService->bumpForEntity($entity);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityClass = get_class($entity);
        
        if (in_array($entityClass, $this->ignoredEntities)) {
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        
        // Skip if no changes or only lastLogin was updated (login events are handled by SecurityEventSubscriber)
        if (empty($changeSet) || 
            (count($changeSet) === 1 && isset($changeSet['lastLogin']))) {
            error_log("EntityUpdateSubscriber: Skipping UPDATE for {$entityClass} - no relevant changes");
            return;
        }

        error_log("EntityUpdateSubscriber: postUpdate called for " . $entityClass);
        $this->logAction('UPDATE', $entity, 'Updated ' . $this->getEntityName($entityClass));
        $this->realtimeVersionService->bumpForEntity($entity);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityClass = get_class($entity);
        
        if (in_array($entityClass, $this->ignoredEntities)) {
            return;
        }

        error_log("EntityUpdateSubscriber: postRemove called for " . $entityClass);
        $this->logAction('DELETE', $entity, 'Deleted ' . $this->getEntityName($entityClass));
        $this->realtimeVersionService->bumpForEntity($entity);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->pendingLogs)) {
            return;
        }

        // Avoid infinite recursion if this postFlush is triggered by our own flush
        if ($this->isFlushingLogs) {
            return;
        }

        $this->isFlushingLogs = true;

        foreach ($this->pendingLogs as $log) {
            $this->entityManager->persist($log);
        }

        $this->pendingLogs = [];

        // Flush only the pending logs; ActivityLog is ignored by this subscriber so no loop
        $this->entityManager->flush();

        $this->isFlushingLogs = false;
    }

    private function logAction(string $action, object $entity, string $description): void
    {
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;
        $entityClass = get_class($entity);

        // Get more specific information based on entity type
        $detailedDescription = $this->getDetailedDescription($action, $entity, $description, $entityId);
        $targetData = $this->getTargetData($entity, $entityId);

        $log = new ActivityLog();
        $log->setAction($action);
        $log->setDescription($detailedDescription);
        $log->setTargetData($targetData);
        $log->setCreatedAt(new \DateTimeImmutable());

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
            $log->setUsername($user->getEmail());
            $log->setRole($user->getRoles()[0] ?? 'ROLE_USER');
            error_log("EntityUpdateSubscriber: Logging {$action} action by user: " . $user->getEmail() . " with role: " . ($user->getRoles()[0] ?? 'ROLE_USER'));
        } else {
            $log->setRole('SYSTEM');
            $log->setUsername('SYSTEM');
            error_log("EntityUpdateSubscriber: Logging {$action} action by SYSTEM (no user found)");
        }

        // Store log to be persisted
        $this->pendingLogs[] = $log;
        error_log("EntityUpdateSubscriber: Added log to pending queue: {$action} - {$detailedDescription}");
    }

    private function getTargetData(object $entity, ?int $entityId): string
    {
        $entityClass = get_class($entity);
        $entityName = $this->getEntityName($entityClass);
        
        if ($entity instanceof User) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $email = $entity->getEmail();
            return "User: {$name} ({$email}) (ID: {$entityId})";
        } elseif ($entity instanceof \App\Entity\Event) {
            $title = $entity->getTitle();
            return "Event: {$title} (ID: {$entityId})";
        } elseif ($entity instanceof \App\Entity\Ticket) {
            $event = $entity->getEvent();
            $eventTitle = $event ? $event->getTitle() : 'Unknown Event';
            $customer = $entity->getCustomer();
            $customerName = $customer ? $customer->getFirstName() . ' ' . $customer->getLastName() : 'Unknown';
            return "Ticket: Event: {$eventTitle}, Customer: {$customerName} (ID: {$entityId})";
        }
        
        return "{$entityName} (ID: {$entityId})";
    }

    private function getDetailedDescription(string $action, object $entity, string $baseDescription, ?int $entityId): string
    {
        $description = $baseDescription;
        
        // Add entity-specific details
        if ($entity instanceof User) {
            $name = $entity->getFirstName() . ' ' . $entity->getLastName();
            $email = $entity->getEmail();
            $role = $entity->getRole();
            $description .= " ({$name}, {$email}, {$role})";
        } elseif ($entity instanceof \App\Entity\Event) {
            $title = $entity->getTitle();
            $description .= " ({$title})";
        } elseif ($entity instanceof \App\Entity\Ticket) {
            $event = $entity->getEvent();
            $eventTitle = $event ? $event->getTitle() : 'Unknown Event';
            $customer = $entity->getCustomer();
            $customerName = $customer ? $customer->getFirstName() . ' ' . $customer->getLastName() : 'Unknown';
            $description .= " (Event: {$eventTitle}, Customer: {$customerName})";
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

