<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheService $cacheService
    ) {}

    public function logTaskCreated(Task $task, ?User $user = null): void
    {
        $log = new ActivityLog();
        $log->setEventType('TASK_CREATED');
        $log->setEventMessage(sprintf('Task "%s" was created', $task->getTitle()));
        $log->setCreatedAt(new \DateTimeImmutable());
        $log->setTask($task);
        $log->setUser($user);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->cacheService->invalidateTaskActivityLogs($task);
    }

    public function logStatusChanged(Task $task, ?User $user, string $oldStatus, string $newStatus): void
    {
        $log = new ActivityLog();
        $log->setEventType('STATUS_CHANGED');
        $log->setEventMessage(sprintf('Status changed from "%s" to "%s"', $oldStatus, $newStatus));
        $log->setCreatedAt(new \DateTimeImmutable());
        $log->setTask($task);
        $log->setUser($user);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->cacheService->invalidateTaskActivityLogs($task);
    }

    public function logTaskDeleted(Task $task, ?User $user = null): void
    {
        $log = new ActivityLog();
        $log->setEventType('TASK_DELETED');
        $log->setEventMessage(sprintf('Task "%s" was deleted', $task->getTitle()));
        $log->setCreatedAt(new \DateTimeImmutable());
        $log->setTask($task);
        $log->setUser($user);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->cacheService->invalidateTaskActivityLogs($task);
    }

    public function logCommentAdded(Task $task, ?User $user = null): void
    {
        $log = new ActivityLog();
        $log->setEventType('COMMENT_ADDED');
        $log->setEventMessage(sprintf('Comment added to task "%s"', $task->getTitle()));
        $log->setCreatedAt(new \DateTimeImmutable());
        $log->setTask($task);
        $log->setUser($user);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->cacheService->invalidateTaskActivityLogs($task);
    }

    public function getLogsForTask(Task $task): array
    {
        return $this->cacheService->getTaskActivityLogs($task, function () use ($task) {
            return $this->entityManager
                ->getRepository(ActivityLog::class)
                ->findBy(['task' => $task], ['createdAt' => 'DESC']);
        });
    }

    /**
     * For read-only view: only show the 3 meaningful event types
     */
    public function getReadOnlyLogsForTask(Task $task): array
    {
        return $this->cacheService->getTaskActivityLogs($task, function () use ($task) {
            return $this->entityManager
                ->getRepository(ActivityLog::class)
                ->createQueryBuilder('a')
                ->where('a.task = :task')
                ->andWhere('a.event_type IN (:types)')
                ->setParameter('task', $task)
                ->setParameter('types', ['TASK_CREATED', 'STATUS_CHANGED', 'TASK_DELETED'])
                ->orderBy('a.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        });
    }
}
