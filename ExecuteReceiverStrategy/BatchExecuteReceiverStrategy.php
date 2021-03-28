<?php

namespace OldSound\RabbitMqBundle\ExecuteReceiverStrategy;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\ExecuteCallbackStrategy\ExecuteReceiverStrategyInterface;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class BatchExecuteReceiverStrategy extends AbstractExecuteReceiverStrategy
{
    /** @var int */
    private $batchCount;
    /** @var AMQPMessage[] */
    protected $messagesBatch = [];

    public function __construct(int $batchCount)
    {
        $this->batchCount = $batchCount;
    }

    public function onConsumeCallback(AMQPMessage $message): void
    {
        $this->messagesBatch[$message->getDeliveryTag()] = $message;

        if ($this->isBatchCompleted()) {
            $this->execute($this->messagesBatch);
        }
    }

    public function onMessageProcessed(AMQPMessage $message)
    {
        if ($this->isBatchEmpty()) {
            throw new \InvalidArgumentException('TODO');
        }
        if ($message !== $this->messagesBatch[array_key_last($this->messagesBatch)]) {
            throw new \InvalidArgumentException('TODO');
        }

        $this->messagesBatch = [];
    }

    public function onStopConsuming()
    {
        if (!$this->isBatchEmpty()) {
            $this->execute($this->messagesBatch);
        }
    }

    protected function isBatchCompleted(): bool
    {
        return count($this->messagesBatch) === $this->batchCount;
    }

    /**
     * @return  bool
     */
    protected function isBatchEmpty()
    {
        return count($this->messagesBatch) === 0;
    }
}