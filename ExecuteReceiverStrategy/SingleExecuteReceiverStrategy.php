<?php

namespace OldSound\RabbitMqBundle\ExecuteReceiverStrategy;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class SingleExecuteReceiverStrategy extends AbstractExecuteReceiverStrategy
{
    /** @var AMQPMessage */
    private $processingMessage;

    public function onConsumeCallback(AMQPMessage $message): void
    {
        $this->processingMessage = $message;
        $this->execute([$this->processingMessage]);
    }

    public function onMessageProcessed(AMQPMessage $message)
    {
        if ($this->processingMessage !== $message) {
            throw new \InvalidArgumentException('TODO');
        }
        $this->processingMessage = null;
    }

    public function onStopConsuming()
    {
        if ($this->processingMessage) {
            $this->execute([$this->processingMessage]);
        }
    }
}