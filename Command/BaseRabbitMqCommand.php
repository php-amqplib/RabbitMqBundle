<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as Command;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

abstract class BaseRabbitMqCommand extends Command
{
    protected function validateConsumer($consumer)
    {
        if (!($consumer instanceof ConsumerInterface)) {
            throw new Exception("The consumer callback has to implement the ConsumerInterface interface");
        }
    }
}