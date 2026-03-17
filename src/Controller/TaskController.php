<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Task;
use App\Form\TaskType;
use App\Form\CommentType;
use App\Service\TaskService;
use App\Service\ActivityLogService;
use App\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

class TaskController extends AbstractController
{
    public function __construct(
        private TaskService $taskService,
        private ActivityLogService $activityLogService,
        private CacheService $cacheService
    ) {}

    #[Route('/tasks', name: 'app_task_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = [
            'status'   => $request->query->get('status', ''),
            'search'   => $request->query->get('search', ''),
            'show'     => $request->query->getInt('show', 5),
            
            // KnpPaginator reads these automatically, but keep them in filters
            // so filter form retains the selected state
            'sort'     => $request->query->get('sort', 't.createdAt'),
            'sort_dir' => $request->query->get('sort_dir', 'DESC'),
        ];

        $page = $request->query->getInt('page', 1);
        $limit = $filters['show'];

        // Get the raw QueryBuilder from service
        $queryBuilder = $this->taskService->getFilteredTasksByUser($this->getUser(), $filters);

        // -- OLD MANUAL APPROACH --
        /*
        $result = $this->taskService->getFilteredTasksByUser($this->getUser(), $filters);
        return $this->render('task/index.html.twig', [
            'tasks'   => $result['tasks'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'pages'   => $result['pages'],
            'perPage' => $result['perPage'],
            'filters' => $filters,
        ]);
        */

        // -- NEW KNP PAGINATOR APPROACH --
        $pagination = $paginator->paginate(
            $queryBuilder,      // The query to paginate
            $page,              // Current page number
            $limit,             // Items per page
            [
                'defaultSortFieldName' => 't.createdAt',
                'defaultSortDirection' => 'desc'
            ]
        );

        return $this->render('task/index.html.twig', [
            'pagination' => $pagination,
            'filters'    => $filters,
        ]);
    }

    private function checkTaskOwner(Task $task): ?Response
    {
        if ($task->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot access this task.');
            return $this->redirectToRoute('app_task_index');
        }
        return null;
    }

    #[Route('/task/new', name: 'app_task_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setCreatedAt(new \DateTimeImmutable());
            $task->setUser($this->getUser());
            $task->setIsDeleted(false);

            $entityManager->persist($task);
            $entityManager->flush();

            $this->activityLogService->logTaskCreated($task, $this->getUser());
            
            // Invalidate task list cache so the new task appears
            $this->cacheService->invalidateUserTasks($this->getUser());

            $this->addFlash('success', 'Task created!');
            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/task/{id}', name: 'app_task_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $task = $this->taskService->getTask($id);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        if ($response = $this->checkTaskOwner($task)) {
            return $response;
        }

        // Only show the 3 allowed event types in activity log
        $activityLogs = $this->activityLogService->getReadOnlyLogsForTask($task);

        // Only build comment form if task is NOT deleted (read-only)
        $commentForm = null;
        if (!$task->isDeleted()) {
            $comment = new Comment();
            $commentForm = $this->createForm(CommentType::class, $comment, [
                'action' => $this->generateUrl('app_comment_add', ['id' => $task->getId()])
            ])->createView();
        }

        return $this->render('task/show.html.twig', [
            'task'         => $task,
            'activityLogs' => $activityLogs,
            'commentForm'  => $commentForm,
            'isReadOnly'   => $task->isDeleted(),
        ]);
    }

    #[Route('/task/{id}/edit', name: 'app_task_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $task = $this->taskService->getTask($id);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        if ($response = $this->checkTaskOwner($task)) {
            return $response;
        }

        // Block editing a soft-deleted task
        if ($task->isDeleted()) {
            $this->addFlash('error', 'This task is deleted and cannot be edited.');
            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        $oldStatus = $task->getStatus();

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            if ($oldStatus !== $task->getStatus()) {
                $this->activityLogService->logStatusChanged(
                    $task,
                    $this->getUser(),
                    $oldStatus,
                    $task->getStatus()
                );
            }

            // Invalidate task list cache so the updated task appears
            $this->cacheService->invalidateUserTasks($this->getUser());

            $this->addFlash('success', 'Task updated successfully!');
            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    // SOFT DELETE — called from show page
    #[Route('/task/{id}/soft-delete', name: 'app_task_soft_delete', methods: ['POST'])]
    public function softDelete(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $task = $this->taskService->getTask($id);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        if ($response = $this->checkTaskOwner($task)) {
            return $response;
        }

        if ($this->isCsrfTokenValid('soft-delete' . $task->getId(), $request->request->get('_token'))) {
            $task->setIsDeleted(true);
            $entityManager->flush();

            // Log the deletion event
            $this->activityLogService->logTaskDeleted($task, $this->getUser());

            // Invalidate task list cache
            $this->cacheService->invalidateUserTasks($this->getUser());

            $this->addFlash('info', 'Task moved to read-only mode.');
        }

        // Stay on show page — now in read-only mode
        return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
    }

    // HARD DELETE — called from index page only
    #[Route('/task/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $task = $this->taskService->getTask($id);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        if ($response = $this->checkTaskOwner($task)) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();

            // Invalidate task list cache
            $this->cacheService->invalidateUserTasks($this->getUser());

            $this->addFlash('success', 'Task permanently deleted.');
        }

        return $this->redirectToRoute('app_task_index');
    }
}
