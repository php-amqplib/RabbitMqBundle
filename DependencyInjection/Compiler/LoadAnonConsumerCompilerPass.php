<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * @author Timothée Barray <tim@amicalement-web.net>
 */
class LoadAnonConsumerCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        foreach ($this->config['anon_consumers'] as $key => $anon) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.anon_consumer.class'));

            $this->injectConnection($definition, $anon['connection']);
            if ($this->enable_collector) {
                $this->injectLoggedChannel($definition, $key, $anon['connection'], $container);
            }
            $definition->addMethodCall('setExchangeOptions', array($anon['exchange_options']));
            $definition->addMethodCall('setCallback', array(array(new Reference($anon['callback']), 'execute')));

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_anon', $key), $definition);
        }
    }
}