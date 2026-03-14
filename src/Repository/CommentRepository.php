<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Get comments for a task with user info, ordered by date
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.task = :task')
            ->setParameter('task', $task)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get comment count for multiple tasks (for task list page)
     */
    public function getCommentCountsForTasks(array $tasks): array
    {
        $taskIds = array_map(fn($task) => $task->getId(), $tasks);

        $results = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.task) as taskId, COUNT(c.id) as commentCount')
            ->where('c.task IN (:taskIds)')
            ->setParameter('taskIds', $taskIds)
            ->groupBy('c.task')
            ->getQuery()
            ->getResult();

        // Convert to associative array: taskId => commentCount
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['taskId']] = $result['commentCount'];
        }

        return $counts;
    }

    /**
     * Get recent comments across all tasks
     */
    public function findRecentComments(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
