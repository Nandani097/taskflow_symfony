<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/tasks', name: 'api_tasks', methods: ['GET'])]
    public function tasks(): JsonResponse
    {
        $results = $this->entityManager->createQueryBuilder()
            ->select('t.id, t.title, t.status, t.priority, t.createdAt, COUNT(c.id) AS comment_count')
            ->from(Task::class, 't')
            ->leftJoin('t.comments', 'c')
            ->where('t.isDeleted = 0')
            ->groupBy('t.id')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        // Format the response
        $tasks = [];
        foreach ($results as $row) {
            $tasks[] = [
                'id'            => $row['id'],
                'title'         => $row['title'],
                'status'        => $row['status'],
                'priority'      => $row['priority'],
                'created_at'    => $row['createdAt']->format('Y-m-d H:i:s'),
                'comment_count' => (int) $row['comment_count'],
            ];
        }

        return new JsonResponse($tasks);
    }
}
