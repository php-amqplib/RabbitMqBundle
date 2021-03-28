<?php

namespace OldSound\RabbitMqBundle\ExecuteReceiverStrategy;

use OldSound\RabbitMqBundle\ReceiverExecutor\ReceiverResultHandlerInterface;
use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractExecuteReceiverStrategy implements ExecuteReceiverStrategyInterface
{
    /** @var callable */
    private $receiver;

    public function setReceiver(callable $receiver): void
    {
        $this->receiver = $receiver;
    }

    /**
     * @param AMQPMessage[] $meesages
     */
    protected function execute(array $messages): void
    {
        $receiver = $this->receiver;
        $receiver($messages);
    }
}