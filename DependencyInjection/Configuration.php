<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Configuration
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class Configuration implements ConfigurationInterface
{
    public function addExchangeConfiguration(NodeBuilder $nb, array $defaultValues = array())
    {
        $defaults = array(
            'passive'     => false,
            'durable'     => true,
            'auto_delete' => false,
            'internal'    => false,
            'nowait'      => false,
            'arguments'   => null,
            'tickets'     => null,
        );

        $defaults = array_merge($defaults, $defaultValues);

        return $nb
            ->arrayNode('exchange_options')
                ->children()
                    ->scalarNode('name')->end()
                    ->scalarNode('type')->end()
                    ->booleanNode('passive')->defaultValue($defaults['passive'])->end()
                    ->booleanNode('durable')->defaultValue($defaults['durable'])->end()
                    ->booleanNode('auto_delete')->defaultValue($defaults['auto_delete'])->end()
                    ->booleanNode('internal')->defaultValue($defaults['internal'])->end()
                    ->booleanNode('nowait')->defaultValue($defaults['nowait'])->end()
                    ->scalarNode('arguments')->defaultValue($defaults['arguments'])->end()
                    ->scalarNode('ticket')->defaultValue($defaults['tickets'])->end()
                ->end()
            ->end()
        ;
    }

    public function addQueueConfiguration(NodeBuilder $nb)
    {
        return $nb
            ->arrayNode('queue_options')
                ->children()
                    ->scalarNode('name')->end()
                    ->booleanNode('passive')->defaultFalse()->end()
                    ->booleanNode('durable')->defaultTrue()->end()
                    ->booleanNode('exclusive')->defaultFalse()->end()
                    ->booleanNode('auto_delete')->defaultFalse()->end()
                    ->booleanNode('nowait')->defaultFalse()->end()
                    ->scalarNode('arguments')->defaultNull()->end()
                    ->scalarNode('ticket')->defaultNull()->end()
                ->end()
            ->end()
        ;
    }

    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $node = $tb
            ->root('old_sound_rabbit_mq')
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->arrayNode('connections')
                    ->useAttributeAsKey('key')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->defaultValue('localhost')->end()
                            ->scalarNode('port')->defaultValue(5672)->end()
                            ->scalarNode('user')->defaultValue('guest')->end()
                            ->scalarNode('password')->defaultValue('guest')->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                        ->end()
                    ->end()
                ->end()
                // producers
                ->arrayNode('producers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end();
                            $node = $this->addExchangeConfiguration($node, array('durable' => false, 'auto_delete' => true, 'internal' => false))
                        ->end()
                    ->end()
                ->end()
                // consumers
                ->arrayNode('consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end();
                            $node = $this->addExchangeConfiguration($node);
                            $node = $this->addQueueConfiguration($node)
                            ->scalarNode('callback')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('rpc_clients')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('rpc_servers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('anon_consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end();
                            $this->addExchangeConfiguration($node)
                            ->scalarNode('callback')->end()
                        ->end()
                    ->end()
            ->end()
        ;

        return $tb;
    }
}

