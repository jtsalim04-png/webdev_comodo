<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Service\RealtimeVersionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
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

    private array $pendingLogs = [];
    private bool $isFlushingLogs = false;
    private bool $pendingRealtimeBump = false;

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private RealtimeVersionService $realtimeVersionService,
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
        if ($this->pendingRealtimeBump) {
            $this->realtimeVersionService->bump();
            $this->pendingRealtimeBump = false;
        }

        if (empty($this->pendingLogs) || $this->isFlushingLogs) {
            return;
        }

        $this->isFlushingLogs = true;

        foreach ($this->pendingLogs as $log) {
            $this->entityManager->persist($log);
        }

        $this->pendingLogs = [];
        $this->entityManager->flush();
        $this->isFlushingLogs = false;
    }

    private function handleEntityChange(LifecycleEventArgs $args, string $action, string $verb): void
    {
        $entity = $args->getObject();
        $entityClass = get_class($entity);

        if (in_array($entityClass, $this->ignoredEntities, true)) {
            return;
        }

        $this->pendingRealtimeBump = true;
        $this->logAction($action, $entity, $verb.' '.$this->getEntityName($entityClass));
    }

    private function logAction(string $action, object $entity, string $description): void
    {
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;

        $log = new ActivityLog();
        $log->setAction($action);
        $log->setDescription($this->getDetailedDescription($action, $entity, $description, $entityId));
        $log->setTargetData($this->getTargetData($entity, $entityId));
        $log->setCreatedAt(new \DateTimeImmutable());

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
            $log->setUsername($user->getEmail());
            $log->setRole($user->getRoles()[0] ?? 'ROLE_USER');
        } else {
            $log->setRole('SYSTEM');
            $log->setUsername('SYSTEM');
        }

        $this->pendingLogs[] = $log;
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
