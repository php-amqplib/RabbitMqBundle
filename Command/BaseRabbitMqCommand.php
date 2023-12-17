<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Command\Command;

abstract class BaseRabbitMqCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
