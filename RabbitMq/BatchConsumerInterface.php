<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface BatchConsumerInterface extends ConsumerInterface
{
    public function batchExecute();
}