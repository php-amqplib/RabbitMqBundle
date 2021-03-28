<?php

namespace OldSound\RabbitMqBundle\ExecuteReceiverStrategy;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;

interface ExecuteReceiverStrategyFactoryInterface
{
    public function create(ConsumeOptions $consumeOptions): ReceiverExecutorInterface;
}