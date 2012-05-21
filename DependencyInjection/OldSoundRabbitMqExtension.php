<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Variable;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * OldSoundRabbitMqExtension.
 *
 * @author Alvaro Videla
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class OldSoundRabbitMqExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;

        $loader = new XmlFileLoader($this->container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('rabbitmq.xml');        
    }

    public function getAlias()
    {
        return 'old_sound_rabbit_mq';
    }
}

