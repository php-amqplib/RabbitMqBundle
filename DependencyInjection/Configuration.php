<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Configuration
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder();

        $tree->root('old_sound_rabbit_mq')
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('enable_collector')->defaultValue(false)->end()
                ->booleanNode('sandbox')->defaultValue(false)->end()
                ->arrayNode('connections')
                    ->useAttributeAsKey('key')
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
                        ->append($this->getExchangeConfiguration())
                        ->append($this->getQueueConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                        ->end()
                    ->end()
                ->end()
                // consumers
                ->arrayNode('consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getExchangeConfiguration())
                        ->append($this->getQueueConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->isRequired()->end()
                            ->arrayNode('qos_options')
                                ->canBeUnset()
                                ->children()
                                    ->scalarNode('prefetch_size')->defaultValue(0)->end()
                                    ->scalarNode('prefetch_count')->defaultValue(0)->end()
                                    ->booleanNode('global')->defaultFalse()->end()
                                ->end()
                            ->end()
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
                            ->scalarNode('callback')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('anon_consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getExchangeConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tree;
    }

    protected function getExchangeConfiguration()
    {
        $node = new ArrayNodeDefinition('exchange_options');

        return $node
            ->children()
                ->scalarNode('name')->end()
                ->scalarNode('type')->end()
                ->booleanNode('passive')->defaultValue(false)->end()
                ->booleanNode('durable')->defaultValue(true)->end()
                ->booleanNode('auto_delete')->defaultValue(false)->end()
                ->booleanNode('internal')->defaultValue(false)->end()
                ->booleanNode('nowait')->defaultValue(false)->end()
                ->variableNode('arguments')->defaultNull()->end()
                ->scalarNode('ticket')->defaultNull()->end()
            ->end()
        ;
    }

    protected function getQueueConfiguration()
    {
        $node = new ArrayNodeDefinition('queue_options');

        return $node
            ->children()
                ->scalarNode('name')->end()
                ->booleanNode('passive')->defaultFalse()->end()
                ->booleanNode('durable')->defaultTrue()->end()
                ->booleanNode('exclusive')->defaultFalse()->end()
                ->booleanNode('auto_delete')->defaultFalse()->end()
                ->booleanNode('nowait')->defaultFalse()->end()
                ->variableNode('arguments')->defaultNull()->end()
                ->scalarNode('ticket')->defaultNull()->end()
                ->arrayNode('routing_keys')
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()
            ->end()
        ;
    }
}
