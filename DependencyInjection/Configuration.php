<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
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
        $rootNode = $tree->root('old_sound_rabbit_mq');

        $rootNode
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('enable_collector')->defaultValue(false)->end()
            ->end()
        ;

        $this->addConnectionsSection($rootNode);
        $this->addExchangesSection($rootNode);
        $this->addProducersSection($rootNode);
        $this->addQueuesSection($rootNode);

//        $rootNode
//            ->children()
//                ->arrayNode('rpc_clients')
//                    ->canBeUnset()
//                    ->useAttributeAsKey('key')
//                    ->prototype('array')
//                        ->children()
//                            ->scalarNode('connection')->defaultValue('default')->end()
//                        ->end()
//                    ->end()
//                ->end()
//                ->arrayNode('rpc_servers')
//                    ->canBeUnset()
//                    ->useAttributeAsKey('key')
//                    ->prototype('array')
//                        ->children()
//                            ->scalarNode('connection')->defaultValue('default')->end()
//                            ->scalarNode('callback')->isRequired()->end()
//                        ->end()
//                    ->end()
//                ->end()
//                ->arrayNode('anon_consumers')
//                    ->canBeUnset()
//                    ->useAttributeAsKey('key')
//                    ->prototype('array')
//                        ->append($this->getExchangeConfiguration())
//                        ->children()
//                            ->scalarNode('connection')->defaultValue('default')->end()
//                            ->scalarNode('callback')->isRequired()->end()
//                        ->end()
//                    ->end()
//                ->end()
//            ->end()
//        ;

        return $tree;
    }

    protected function addConnectionsSection(ArrayNodeDefinition $node)
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('connections')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->booleanNode('is_default')->defaultFalse()->end()
                            ->scalarNode('host')->defaultValue('localhost')->end()
                            ->scalarNode('port')->defaultValue(5672)->end()
                            ->scalarNode('user')->defaultValue('guest')->end()
                            ->scalarNode('password')->defaultValue('guest')->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addExchangesSection(ArrayNodeDefinition $node)
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('exchanges')
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->end()
                            ->booleanNode('passive')->defaultValue(false)->end()
                            ->booleanNode('durable')->defaultValue(true)->end()
                            ->booleanNode('auto_delete')->defaultValue(false)->end()
                            ->booleanNode('internal')->defaultValue(false)->end()
                            ->booleanNode('nowait')->defaultValue(false)->end()
                            ->variableNode('arguments')->defaultNull()->end()
                            ->scalarNode('ticket')->defaultNull()->end()
                            ->arrayNode('bindings')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('queue')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('routing_key')->defaultNull()->end()
                                    ->end()
                                ->end()
                                ->defaultValue(array())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addProducersSection(ArrayNodeDefinition $node)
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('producers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
//                        ->append($this->getExchangeConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('exchange')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addQueuesSection(ArrayNodeDefinition $node)
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('queues')
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->booleanNode('passive')->defaultFalse()->end()
                            ->booleanNode('durable')->defaultTrue()->end()
                            ->booleanNode('exclusive')->defaultFalse()->end()
                            ->booleanNode('auto_delete')->defaultFalse()->end()
                            ->booleanNode('nowait')->defaultFalse()->end()
                            ->variableNode('arguments')->defaultNull()->end()
                            ->scalarNode('ticket')->defaultNull()->end()
                            ->arrayNode('bindings')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('exchange')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('routing_key')->defaultNull()->end()
                                    ->end()
                                ->end()
                                ->defaultValue(array())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}

