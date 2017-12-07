<?php

namespace OldSound\RabbitMqBundle\Command;


use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Psr\Container\ContainerInterface as PsrContainerInterface;

abstract class BaseRabbitMqCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PsrContainerInterface
     */
    protected $psrContainer;

    /**
     * {@inheritDoc}
     */
    public function __construct($name = null, PsrContainerInterface $container = null)
    {
        $this->psrContainer = $container;
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface|PsrContainerInterface
     */
    public function getContainer()
    {
        return $this->psrContainer ?: $this->psrContainer;
    }
}
