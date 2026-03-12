<?php

namespace App\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    private $taskRepository;
    private $entityManager;

    public function __construct(TaskRepository $taskRepository, EntityManagerInterface $entityManager)
    {
        $this->taskRepository = $taskRepository;
        $this->entityManager = $entityManager;
    }

    public function getAllTasks()
    {
        return $this->taskRepository->findAll();
    }

    public function getTask(int $id): ?Task
    {
        return $this->taskRepository->find($id);
    }

    public function createTask(string $title, string $description): void
    {
        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setStatus('pending');
        $task->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function updateTask(Task $task, array $data): void
    {
        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        if (isset($data['priority'])) {
            $task->setPriority($data['priority']);
        }

        $task->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }


    public function deleteTask(Task $task): void
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }
}