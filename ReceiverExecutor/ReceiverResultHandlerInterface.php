<?php

namespace OldSound\RabbitMqBundle\ReceiverExecutor;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;

interface ReceiverResultHandlerInterface
{
    /**
     * @param mixed $result
     * @param array $messages
     */
    public function handle($result, array $messages, ConsumeOptions $options): void;
}