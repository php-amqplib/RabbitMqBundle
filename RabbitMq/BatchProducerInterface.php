<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface BatchProducerInterface extends ProducerInterface
{
    public function send();
}
