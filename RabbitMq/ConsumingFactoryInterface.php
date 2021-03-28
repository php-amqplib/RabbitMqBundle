<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;

interface ConsumingFactoryInterface
{
    public function create(ConsumeOptions $consumerOptions): Consuming;
}