<?php

namespace App\Controller;

use App\Entity\Task;                    // <-- ADD THIS
use App\Form\TaskType;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface; // <-- ADD THIS
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    // Constructor injection of TaskService
    public function __construct(
        private TaskService $taskService
    ) {}

    // LIST all tasks
    #[Route('/tasks', name: 'app_task_index')]
    public function index(TaskService $taskService): Response
    {
        $tasks = $taskService->getAllTasks();

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    // SHOW single task
    #[Route('/task/{id}', name: 'app_task_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $task = $this->taskService->getTask($id);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    // // CREATE new task (form + submit)
    // #[Route('/task/new', name: 'app_task_new')]
    // public function new(Request $request): Response
    // {
    //     if ($request->isMethod('POST')) {
    //         $title = $request->request->get('title');
    //         $description = $request->request->get('description');
    //         $status = $request->request->get('status', 'pending');
    //         $priority = $request->request->get('priority', 'medium');

    //         // Call service to create task
    //         $this->taskService->createTask($title, $description, $status);

    //         $this->addFlash('success', 'Task created successfully!');
    //         return $this->redirectToRoute('app_task_index');
    //     }

    //     return $this->render('task/new.html.twig');
    // }

   // CREATE new task Using symfony form component
    #[Route('/task/new', name: 'app_task_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();

        // Create the form
        $form = $this->createForm(TaskType::class, $task);

        // Handle request (automatic data binding)
        $form->handleRequest($request);

        // Check if form was submitted and is valid
        if ($form->isSubmitted() && $form->isValid()) {
            $task->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'Task created!');
            return $this->redirectToRoute('app_task_index');
        }

        // Pass form to template
        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // EDIT task Using Symfony Form Component
    #[Route('/task/{id}/edit', name: 'app_task_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $task = $this->taskService->getTask($id);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        // Create form pre-filled with existing task data
        $form = $this->createForm(TaskType::class, $task);

        // Handle request (automatic data binding)
        $form->handleRequest($request);

        // Check if form was submitted and is valid
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // No need to persist — task already exists in DB

            $this->addFlash('success', 'Task updated successfully!');
            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    // DELETE task
    #[Route('/task/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $task = $this->taskService->getTask($id);

        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        // CSRF protection
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $this->taskService->deleteTask($task);
            $this->addFlash('success', 'Task deleted successfully!');
        }

        return $this->redirectToRoute('app_task_index');
    }
}
