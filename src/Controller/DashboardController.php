<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET', 'POST'])]
    public function index(TaskRepository $repo, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($user);
            $task->setStatus('new');
            $task->setCreatedAt(new \DateTimeImmutable());

            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $repo->findBy(['user' => $user], ['createdAt' => 'DESC']),
            'task_form' => $form->createView(),
        ]);
    }
}
