<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * Configuration
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class Configuration implements ConfigurationInterface
{
    /** @var string */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder($this->name);
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $tree->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('enable_collector')->defaultValue(false)->end()
                ->booleanNode('sandbox')->defaultValue(false)->end()
            ->end()
        ;

        $this->addConnections($rootNode);
        $this->addDeclarations($rootNode);
        $this->addProducers($rootNode);

        $rootNode
            ->children()
                ->arrayNode('default_channel_optinos')->end()
            ->end()
        ;
        $this->addConsumers($rootNode);

        return $tree;
    }

    protected function addDeclarations(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('declaration')
            ->children()
                ->arrayNode('exchanges')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('type')->isRequired()->end()
                            ->booleanNode('passive')->defaultValue(false)->end()
                            ->booleanNode('durable')->defaultValue(true)->end()
                            ->booleanNode('auto_delete')->defaultValue(false)->end()
                            ->booleanNode('internal')->defaultValue(false)->end()
                            ->booleanNode('nowait')->defaultValue(false)->end()
                            ->booleanNode('declare')->defaultValue(true)->end()
                            ->variableNode('arguments')->defaultNull()->end()
                            ->scalarNode('ticket')->defaultNull()->end()
                            ->arrayNode('bindings')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('destination')->isRequired()->end()
                                        ->booleanNode('destination_is_exchange')->defaultFalse()->end()
                                        ->scalarNode('routing_key')->defaultValue(null)->end()
                                        ->arrayNode('routing_keys')->defaultValue([])->scalarPrototype()->end()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('queues')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->end()
                            ->booleanNode('anon')->defaultFalse()->end()
                            ->booleanNode('passive')->defaultFalse()->end()
                            ->booleanNode('durable')->defaultTrue()->end()
                            ->booleanNode('exclusive')->defaultFalse()->end()
                            ->booleanNode('auto_delete')->defaultFalse()->end()
                            ->booleanNode('nowait')->defaultFalse()->end()
                            ->booleanNode('declare')->defaultTrue()->end()
                            ->variableNode('arguments')->defaultNull()->end()
                            ->scalarNode('ticket')->defaultNull()->end()
                            ->arrayNode('bindings')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('exchange')->end()
                                        ->scalarNode('routing_key')->defaultValue(null)->end()
                                        ->arrayNode('routing_keys')->defaultValue([])->scalarPrototype()->end()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('bindings')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('exchange')->isRequired()->end()
                            ->scalarNode('destination')->isRequired()->end()
                            ->booleanNode('destination_is_exchange')->defaultFalse()->end()
                            ->scalarNode('routing_key')->defaultValue(null)->end()
                            ->arrayNode('routing_keys')->defaultValue([])->scalarPrototype()->end()->end()
                        ->end()
                    ->end()
                ->end()
        ;
    }

    protected function addConnections(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('connection')
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('key')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('url')->defaultValue('')->end()
                            ->scalarNode('host')->defaultValue('localhost')->end()
                            ->scalarNode('port')->defaultValue(5672)->end()
                            ->scalarNode('user')->defaultValue('guest')->end()
                            ->scalarNode('password')->defaultValue('guest')->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                            ->booleanNode('lazy')->defaultTrue()->end()
                            ->scalarNode('connection_timeout')->defaultValue(3)->end()
                            ->scalarNode('read_write_timeout')->defaultValue(3)->end()
                            ->booleanNode('use_socket')->defaultValue(false)->end()
                            ->arrayNode('ssl_context')
                                ->useAttributeAsKey('key')
                                ->canBeUnset()
                                ->prototype('variable')->end()
                            ->end()
                            ->booleanNode('keepalive')->defaultFalse()->info('requires php-amqplib v2.4.1+ and PHP5.4+')->end()
                            ->scalarNode('heartbeat')->defaultValue(0)->info('requires php-amqplib v2.4.1+')->end()
                            ->scalarNode('connection_parameters_provider')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addProducers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('producer')
            ->children()
                ->arrayNode('producers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('exchange')->defaultValue('')->end()
                            ->scalarNode('auto_declare')->defaultValue('%kernel.debug%')->end()
                            ->scalarNode('additional_properties')->end()
                            ->scalarNode('logging')->defaultTrue()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }


    protected function addConsumers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('consumer')
            ->children()
                ->arrayNode('consumers')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('logging')->defaultTrue()->end()
                            ->arrayNode('consume')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('queue')->isRequired()->end()
                                        ->scalarNode('receiver')->isRequired()->end()
                                        ->scalarNode('qos_prefetch_size')->defaultValue(0)->end()
                                        ->scalarNode('qos_prefetch_count')->defaultValue(0)->end()
                                        ->scalarNode('batch_count')->end()
                                        ->booleanNode('exclusive')->end()
                                        ->booleanNode('auto_delete')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('idle_timeout')->end()
                            ->scalarNode('idle_timeout_exit_code')->end()
                            ->scalarNode('timeout_wait')->end()
                            ->arrayNode('graceful_max_execution')
                                ->canBeUnset()
                                ->children()
                                    ->integerNode('timeout')->end()
                                    ->integerNode('exit_code')->defaultValue(0)->end()
                                ->end()
                            ->end()
                            ->scalarNode('auto_setup_fabric')->defaultTrue()->end()
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
            ->end()
        ;
    }

}
