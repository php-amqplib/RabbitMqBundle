<?php

namespace OldSound\RabbitMqBundle\ExecuteReceiverStrategy;

use OldSound\RabbitMqBundle\ReceiverExecutor\ReceiverResultHandlerInterface;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

interface ExecuteReceiverStrategyInterface
{
    public function setReceiver(callable $receiver);

    public function onConsumeCallback(AMQPMessage $message);

    public function onMessageProcessed(AMQPMessage $message);

    public function onStopConsuming();
}