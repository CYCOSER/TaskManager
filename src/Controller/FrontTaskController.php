<?php

namespace App\Controller;

use App\Entity\Task;
use App\Message\EventMessage;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class FrontTaskController extends AbstractController
{
    #[Route('', name: 'app_task_list', methods: ['GET', 'POST'])]
    public function index(Request $request, TaskRepository $repo, EntityManagerInterface $em, MessageBusInterface $bus): Response
    {
        // Обработка создания задачи прямо здесь (для модального окна)
        if ($request->isMethod('POST') && $request->request->has('create_task')) {
            $task = new Task();
            $task->setTitle($request->request->get('title'));
            $task->setDescription($request->request->get('description'));
            $task->setStatus('new');
            $task->setUser($this->getUser());
            $task->setCreatedAt(new \DateTimeImmutable());

            $em->persist($task);
            $em->flush();

            $bus->dispatch(new EventMessage("Задача создана через модальное окно: " . $task->getTitle()));

            return $this->redirectToRoute('app_task_list');
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $repo->findBy(['user' => $this->getUser()]),
        ]);
    }
}
