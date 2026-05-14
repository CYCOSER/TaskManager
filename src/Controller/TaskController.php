<?php

namespace App\Controller;

use App\Entity\Task;
use App\Message\EventMessage;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use OpenApi\Attributes as OA;


#[Route('/api/tasks', name: 'task_')]
class TaskController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Tag(name: 'Tasks')]
    #[OA\Get(
        description: 'Возвращает массив задач текущего авторизованного пользователя. Данные кэшируются в Redis для ускорения работы.',
        summary: 'Получение списка всех задач',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный возврат списка задач'
            ),
            new OA\Response(
                response: 401,
                description: 'JWT токен не предоставлен или невалиден'
            )
        ]
    )]
    #[OA\Parameter(
        name: 'status',
        description: 'Фильтр по статусу (например: new, done)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'new')
    )]
    public function index(Request $request, TaskRepository $repo, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        $status = $request->query->get('status');

        $cacheKey = 'tasks_user_' . $user->getId() . '_' . ($status ?? 'all');

        $tasks = $cache->get($cacheKey, function (ItemInterface $item) use ($repo, $user, $status) {
            $item->expiresAfter(3600);

            $criteria = ['user' => $user];
            if ($status) {
                $criteria['status'] = $status;
            }

            return $repo->findBy($criteria);
        });

        return $this->json($tasks);
    }
    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Tag(name: 'Tasks')]
    #[OA\Post(
        summary: 'Создание задачи (асинхронно уведомляет RabbitMQ)',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Очень важная задача'),
                    new OA\Property(property: 'description', type: 'string', example: 'Очень надо сделать'),
                    new OA\Property(property: 'status', type: 'string', example: 'new')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Задача создана'),
            new OA\Response(response: 401, description: 'Необходим JWT токен')
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em, MessageBusInterface $bus, CacheInterface $cache): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || trim((string)$data['title']) === '') {
            return $this->json(['error' => 'Title is required and cannot be empty'], 400);
        }

        $user = $this->getUser();

        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? '');
        $task->setStatus($data['status'] ?? 'new');
        $task->setUser($user);
        $task->setCreatedAt(new \DateTimeImmutable());

        $em->persist($task);
        $em->flush();

        $cache->delete('tasks_user_' . $user->getUserIdentifier());

        $bus->dispatch(new EventMessage("Создана задача: " . $task->getTitle()));

        return $this->json($task, 201, [], ['groups' => 'task:read']);
    }
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Tag(name: 'Tasks')]
    #[OA\Put(
        description: 'Обновляет поля задачи по её ID. Доступно только владельцу.',
        summary: 'Обновление существующей задачи',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Очень новое название'),
                    new OA\Property(property: 'status', type: 'string', example: 'done'),
                    new OA\Property(property: 'description', type: 'string', example: 'Очень новое описание')
                ]
            )
        ),
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID задачи', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Задача обновлена и кэш Redis очищен'),
            new OA\Response(response: 404, description: 'Задача не найдена или не принадлежит вам'),
            new OA\Response(response: 401, description: 'Необходим JWT токен')
        ]
    )]
    public function update(int $id, Request $request, TaskRepository $repo, EntityManagerInterface $em, CacheInterface $cache): JsonResponse
    {
        $task = $repo->findOneBy(['id' => $id, 'user' => $this->getUser()]);
        if (!$task) {
            return $this->json(['error' => 'Task not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['title'])) $task->setTitle($data['title']);
        if (isset($data['status'])) $task->setStatus($data['status']);
        if (isset($data['description'])) $task->setDescription($data['description']);

        $em->flush();

        $userKey = str_replace(['@', '.'], '_', $this->getUser()->getUserIdentifier());

        $cache->delete('tasks_user_' . $userKey);

        return $this->json($task, 200, [], ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Tasks')]
    #[OA\Delete(
        description: 'Удаляет задачу по ID и очищает кэш пользователя в Redis.',
        summary: 'Удаление задачи',
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID задачи', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Задача успешно удалена'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
            new OA\Response(response: 401, description: 'Необходим JWT токен')
        ]
    )]
    public function delete(int $id, TaskRepository $repo, EntityManagerInterface $em, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        $task = $repo->findOneBy(['id' => $id, 'user' => $user]);

        if (!$task) {
            return $this->json(['error' => 'Task not found'], 404);
        }

        $em->remove($task);
        $em->flush();

        $cache->delete('tasks_user_' . $user->getId() . '_all');

        $cache->delete('tasks_user_' . $user->getId() . '_new');
        $cache->delete('tasks_user_' . $user->getId() . '_done');

        return $this->json(null, 204);
    }
}
