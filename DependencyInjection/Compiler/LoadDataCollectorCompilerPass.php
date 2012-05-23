<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Timothée Barray <tim@amicalement-web.net>
 */
class LoadDataCollectorCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        if ($this->enableCollector) {        
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.data_collector.class'));
            $channels = array();

            foreach ($container->findTaggedServiceIds('old_sound_rabbit_mq.logged_channel') as $id => $params) {
                $channels[] = new Reference($id);
            }

            $container->setDefinition('data_collector.rabbit_mq', $definition)
                        ->addArgument($channels)
                        ->addTag('data_collector', array(
                            'template' => 'OldSoundRabbitMqBundle:Collector:collector.html.twig',
                            'id'       => 'rabbit_mq',
                        ));
        }
    }
}