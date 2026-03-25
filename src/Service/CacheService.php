<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Comment;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    // Cache TTL: 1 hour
    private const CACHE_TTL = 3600;

    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    // ──────────────── Task List Cache ────────────────

    public function getUserTasks(User $user, callable $fetchCallback): array
    {
        $key = 'user_tasks_' . $user->getId();
        $cacheHit = true;

        $taskIds = $this->cache->get($key, function (ItemInterface $item) use ($fetchCallback, $key, &$cacheHit) {
            $item->expiresAfter(self::CACHE_TTL);
            
            $cacheHit = false;
            $tasks = $fetchCallback();
            $ids = array_map(fn(Task $task) => $task->getId(), $tasks);
            
            $this->logger->info('Cache MISS: "{key}" — fetched {count} tasks from database, stored in Redis', [
                'key' => $key,
                'count' => count($ids),
            ]);
            return $ids;
        });

        if ($cacheHit) {
            $this->logger->info('Cache HIT: "{key}" — served {count} task IDs from Redis', [
                'key' => $key,
                'count' => count($taskIds),
            ]);
        }

        if (empty($taskIds)) {
            return [];
        }

        return $this->entityManager
            ->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $taskIds)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function invalidateUserTasks(User $user): void
    {
        $key = 'user_tasks_' . $user->getId();
        $this->cache->delete($key);
        
        $this->logger->info('Cache INVALIDATED: "{key}" — task list cache cleared', [
            'key' => $key,
        ]);
    }

    // ──────────────── Activity Log Cache ────────────────

    public function getTaskActivityLogs(Task $task, callable $fetchCallback): array
    {
        $key = 'task_logs_' . $task->getId();
        $cacheHit = true;

        $logIds = $this->cache->get($key, function (ItemInterface $item) use ($fetchCallback, $key, &$cacheHit) {
            $item->expiresAfter(self::CACHE_TTL);
            
            $cacheHit = false;
            $logs = $fetchCallback();
            $ids = array_map(fn(ActivityLog $log) => $log->getId(), $logs);
            
            $this->logger->info('Cache MISS: "{key}" — fetched {count} activity logs from DB, stored in Redis', [
                'key' => $key,
                'count' => count($ids),
            ]);
            return $ids;
        });

        if ($cacheHit) {
            $this->logger->info('Cache HIT: "{key}" — served {count} activity log IDs from Redis', [
                'key' => $key,
                'count' => count($logIds),
            ]);
        }

        if (empty($logIds)) {
            return [];
        }

        return $this->entityManager
            ->getRepository(ActivityLog::class)
            ->createQueryBuilder('a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $logIds)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function invalidateTaskActivityLogs(Task $task): void
    {
        $key = 'task_logs_' . $task->getId();
        $this->cache->delete($key);
        
        $this->logger->info('Cache INVALIDATED: "{key}" — activity log caches cleared', [
            'key' => $key,
        ]);
    }

    // ──────────────── Comment Cache ────────────────

    public function getTaskComments(Task $task, callable $fetchCallback): array
    {
        $key = 'task_comments_' . $task->getId();
        $cacheHit = true;

        $commentIds = $this->cache->get($key, function (ItemInterface $item) use ($fetchCallback, $key, &$cacheHit) {
            $item->expiresAfter(self::CACHE_TTL);
            
            $cacheHit = false;
            $comments = $fetchCallback();
            $ids = array_map(fn(Comment $comment) => $comment->getId(), $comments);
            
            $this->logger->info('Cache MISS: "{key}" — fetched {count} comments from DB, stored in Redis', [
                'key' => $key,
                'count' => count($ids),
            ]);
            return $ids;
        });

        if ($cacheHit) {
            $this->logger->info('Cache HIT: "{key}" — served {count} comment IDs from Redis', [
                'key' => $key,
                'count' => count($commentIds),
            ]);
        }

        if (empty($commentIds)) {
            return [];
        }

        return $this->entityManager
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $commentIds)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function invalidateTaskComments(Task $task): void
    {
        $key = 'task_comments_' . $task->getId();
        $this->cache->delete($key);
        
        $this->logger->info('Cache INVALIDATED: "{key}" — comment caches cleared', [
            'key' => $key,
        ]);
    }
}
