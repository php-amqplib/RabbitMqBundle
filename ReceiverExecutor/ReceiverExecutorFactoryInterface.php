<?php

namespace OldSound\RabbitMqBundle\ReceiverExecutor;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;

interface ReceiverExecutorFactoryInterface
{
    public function create(ConsumeOptions $consumeOptions): ReceiverResultHandlerInterface;
}