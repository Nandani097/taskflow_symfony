<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Get activity logs for a task
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.task = :task')
            ->setParameter('task', $task)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
