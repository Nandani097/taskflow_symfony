<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Add a comment to a task
     */
    public function addComment(Task $task, User $user, string $content): Comment
    {
        $comment = new Comment();
        $comment->setContent($content);
        $comment->setTask($task);
        $comment->setUser($user);
        $comment->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    /**
     * Get all comments for a task
     */
    public function getCommentsForTask(Task $task): array
    {
        return $this->commentRepository->findByTask($task);
    }

    /**
     * Get comment counts for multiple tasks (for task list)
     */
    public function getCommentCounts(array $tasks): array
    {
        return $this->commentRepository->getCommentCountsForTasks($tasks);
    }

    /**
     * Delete a comment
     */
    public function deleteComment(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }
}
