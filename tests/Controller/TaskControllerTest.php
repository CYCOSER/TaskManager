<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private User $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'testuser']);

        if (!$user) {
            $user = new User();
            $user->setEmail('testuser');
            $user->setPassword('password123');
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $testUserInDb = $userRepository->findOneBy(['email' => 'testuser']);
        if (!$testUserInDb) {
            $this->fail('Failed to clear or prepare test user inside database.');
        }

        $this->testUser = $testUserInDb;
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

        parent::tearDown();
        $this->entityManager->close();
    }

    public function testIndexReturnsUnauthorizedForGuests(): void
    {
        $this->client->request('GET', '/api/tasks');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testIndexReturnsSuccessForUser(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/tasks');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testCreateTaskSuccessfully(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'New Task Title',
                'description' => 'Task Description',
                'status' => 'pending'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);

        $task = $this->entityManager->getRepository(Task::class)->find($responseData['id']);

        $this->assertNotNull($task);
        $this->assertSame('New Task Title', $task->getTitle());
        $this->assertSame('pending', $task->getStatus());
        $this->assertSame($this->testUser->getId(), $task->getUser()->getId());
    }

    public function testCreateTaskFailsWithoutTitle(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'description' => 'Missing title',
                'status' => 'pending'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateTaskSuccessfully(): void
    {
        $this->client->loginUser($this->testUser);

        $task = new Task();
        $task->setTitle('Old Title');
        $task->setStatus('pending');
        $task->setUser($this->testUser);
        $task->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->client->request(
            'PUT',
            '/api/tasks/' . $task->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Updated Title',
                'status' => 'completed'
            ])
        );

        $this->assertResponseIsSuccessful();

        $this->entityManager->refresh($task);
        $this->assertSame('Updated Title', $task->getTitle());
        $this->assertSame('completed', $task->getStatus());
    }

    public function testDeleteTaskSuccessfully(): void
    {
        $this->client->loginUser($this->testUser);

        $task = new Task();
        $task->setTitle('To Be Deleted');
        $task->setStatus('pending');
        $task->setUser($this->testUser);
        $task->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $id = $task->getId();

        $this->client->request('DELETE', '/api/tasks/' . $id);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $deletedTask = $this->entityManager->getRepository(Task::class)->find($id);
        $this->assertNull($deletedTask);
    }
}
