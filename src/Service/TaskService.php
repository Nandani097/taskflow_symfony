<?php

namespace App\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private ActivityLogService $activityLogService,
        private CacheService $cacheService
    ) {}

    public function getTask(int $id): ?Task
    {
        return $this->taskRepository->find($id);
    }

    public function getActiveTasksByUser($user): array
    {
        return $this->cacheService->getUserTasks($user, function () use ($user) {
            return $this->entityManager
                ->createQueryBuilder()
                ->select('t')
                ->from(Task::class, 't')
                ->where('t.user = :user')
                ->setParameter('user', $user)
                ->orderBy('t.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        });
    }

    public function getTasksByUser($user): array
    {
        return $this->taskRepository->findBy(['user' => $user]);
    }

    public function createTask(string $title, string $description, string $status = 'pending'): Task
    {
        $user = $this->security->getUser();

        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setStatus($status);
        $task->setPriority('medium');
        $task->setCreatedAt(new \DateTimeImmutable());
        $task->setUser($user);
        $task->setIsDeleted(false);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->activityLogService->logTaskCreated($task, $user);

        // Invalidate task list cache
        $this->cacheService->invalidateUserTasks($user);

        return $task;
    }

    public function updateTask(Task $task, array $data): void
    {
        $user = $this->security->getUser();
        $oldStatus = $task->getStatus();
        $statusChanged = false;

        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        if (isset($data['priority'])) {
            $task->setPriority($data['priority']);
        }
        if (isset($data['status']) && $task->getStatus() !== $data['status']) {
            $task->setStatus($data['status']);
            $statusChanged = true;
        }

        $task->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        if ($statusChanged) {
            $this->activityLogService->logStatusChanged($task, $user, $oldStatus, $data['status']);
        }

        // Invalidate task list cache
        $this->cacheService->invalidateUserTasks($user);
    }

    public function deleteTask(Task $task): void
    {
        $user = $this->security->getUser();
        $this->activityLogService->logTaskDeleted($task, $user);
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        // Invalidate task list cache
        $this->cacheService->invalidateUserTasks($user);
    }
}
