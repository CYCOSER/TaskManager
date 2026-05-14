<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidTask(): void
    {
        $task = new Task();
        $task->setTitle('Valid Title');
        $task->setStatus('pending');
        $task->setCreatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($task);
        $this->assertCount(0, $errors);
    }

    public function testInvalidTaskBlankTitle(): void
    {
        $task = new Task();
        $task->setTitle('');
        $task->setStatus('pending');
        $task->setCreatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($task);

        $this->assertGreaterThan(0, count($errors));
    }
}
