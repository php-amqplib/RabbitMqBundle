<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface ConsumerInterface
{
    function execute($msg);
}