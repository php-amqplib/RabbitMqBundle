<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface DequeuerAwareInterface
{
    public function setDequeuer(DequeuerInterface $dequeuer);
}
