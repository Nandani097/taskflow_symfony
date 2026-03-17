<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Task;
use App\Form\CommentType;
use App\Service\ActivityLogService;
use App\Service\CommentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommentController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private CommentService $commentService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/task/{id}/comment', name: 'app_comment_add', methods: ['POST'])]
    public function addComment(Request $request, Task $task): Response
    {
        // $user = $this->getUser();
        // dd($user);
        if ($task->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot comment on this task');
            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        } 

        // Use CommentType form — validates, checks CSRF, uses CommentService
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Delegate saving to CommentService
            $this->commentService->addComment($task, $this->getUser(), $comment->getContent());

            // Log via service
            $this->activityLogService->logCommentAdded($task, $this->getUser());

            $this->addFlash('success', 'Comment added!');
        } else {
            // Form failed validation — pass errors back as flash messages
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
    }

    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Comment $comment): Response
    {
        if ($comment->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot delete this comment');
            return $this->redirectToRoute('app_task_show', ['id' => $comment->getTask()->getId()]);
        }

        $task = $comment->getTask();

        if ($this->isCsrfTokenValid('delete-comment' . $comment->getId(), $request->request->get('_token'))) {
            $this->commentService->deleteComment($comment);
            $this->addFlash('success', 'Comment deleted');
        }

        return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
    }
}
