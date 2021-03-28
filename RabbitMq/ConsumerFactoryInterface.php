<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Declarations\ConsumerDef;

interface ConsumerFactoryInterface
{
    public function create(ConsumerDef $consumerDef): Consumer;
}