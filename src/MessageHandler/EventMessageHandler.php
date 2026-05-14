<?php
namespace App\MessageHandler;
use App\Message\EventMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EventMessageHandler {
    public function __invoke(EventMessage $message) {
        file_put_contents('var/log/messenger.log', $message->getContent() . PHP_EOL, FILE_APPEND);
    }
}
