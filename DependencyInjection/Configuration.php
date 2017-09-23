<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Configuration
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * Configuration constructor.
     *
     * @param   string  $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder();

        $rootNode = $tree->root($this->name);

        $rootNode
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('enable_collector')->defaultValue(false)->end()
                ->booleanNode('sandbox')->defaultValue(false)->end()
            ->end()
        ;

        $this->addConnections($rootNode);
        $this->addBindings($rootNode);
        $this->addProducers($rootNode);
        $this->addConsumers($rootNode);
        $this->addMultipleConsumers($rootNode);
        $this->addDynamicConsumers($rootNode);
        $this->addBatchConsumers($rootNode);
        $this->addAnonConsumers($rootNode);
        $this->addRpcClients($rootNode);
        $this->addRpcServers($rootNode);

        return $tree;
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
                            ->booleanNode('lazy')->defaultFalse()->end()
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
                        ->append($this->getExchangeConfiguration())
                        ->append($this->getQueueConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('auto_setup_fabric')->defaultTrue()->end()
                            ->scalarNode('class')->defaultValue('%old_sound_rabbit_mq.producer.class%')->end()
                            ->scalarNode('enable_logger')->defaultFalse()->end()
                            ->scalarNode('service_alias')->defaultValue(null)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addBindings(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('binding')
            ->children()
                ->arrayNode('bindings')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('exchange')->defaultNull()->end()
                            ->scalarNode('destination')->defaultNull()->end()
                            ->scalarNode('routing_key')->defaultNull()->end()
                            ->booleanNode('nowait')->defaultFalse()->end()
                            ->booleanNode('destination_is_exchange')->defaultFalse()->end()
                            ->variableNode('arguments')->defaultNull()->end()
                            ->scalarNode('class')->defaultValue('%old_sound_rabbit_mq.binding.class%')->end()
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
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getExchangeConfiguration())
                        ->append($this->getQueueConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->isRequired()->end()
                            ->scalarNode('idle_timeout')->end()
                            ->scalarNode('idle_timeout_exit_code')->end()
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
                            ->scalarNode('enable_logger')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addMultipleConsumers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('multiple_consumer')
            ->children()
                ->arrayNode('multiple_consumers')
                ->canBeUnset()
                ->useAttributeAsKey('key')
                ->prototype('array')
                    ->append($this->getExchangeConfiguration())
                    ->children()
                        ->scalarNode('connection')->defaultValue('default')->end()
                        ->scalarNode('idle_timeout')->end()
                        ->scalarNode('idle_timeout_exit_code')->end()
                        ->scalarNode('auto_setup_fabric')->defaultTrue()->end()
                        ->append($this->getMultipleQueuesConfiguration())
                        ->arrayNode('qos_options')
                            ->canBeUnset()
                            ->children()
                                ->scalarNode('prefetch_size')->defaultValue(0)->end()
                                ->scalarNode('prefetch_count')->defaultValue(0)->end()
                                ->booleanNode('global')->defaultFalse()->end()
                            ->end()
                        ->end()
                        ->scalarNode('queues_provider')->defaultNull()->end()
                        ->scalarNode('enable_logger')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
    
    protected function addDynamicConsumers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('dynamic_consumer')
            ->children()
                ->arrayNode('dynamic_consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getExchangeConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->isRequired()->end()
                            ->scalarNode('idle_timeout')->end()
                            ->scalarNode('idle_timeout_exit_code')->end()
                            ->scalarNode('auto_setup_fabric')->defaultTrue()->end()
                            ->arrayNode('qos_options')
                                ->canBeUnset()
                                ->children()
                                    ->scalarNode('prefetch_size')->defaultValue(0)->end()
                                    ->scalarNode('prefetch_count')->defaultValue(0)->end()
                                    ->booleanNode('global')->defaultFalse()->end()
                                ->end()
                            ->end()
                            ->scalarNode('queue_options_provider')->isRequired()->end()
                            ->scalarNode('enable_logger')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param   ArrayNodeDefinition     $node
     *
     * @return  void
     */
    protected function addBatchConsumers(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('batch_consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getExchangeConfiguration())
                        ->append($this->getQueueConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->isRequired()->end()
                            ->scalarNode('idle_timeout')->end()
                            ->scalarNode('timeout_wait')->defaultValue(3)->end()
                            ->scalarNode('idle_timeout_exit_code')->end()
                            ->scalarNode('auto_setup_fabric')->defaultTrue()->end()
                            ->arrayNode('qos_options')
                                ->children()
                                    ->scalarNode('prefetch_size')->defaultValue(0)->end()
                                    ->scalarNode('prefetch_count')->defaultValue(2)->end()
                                    ->booleanNode('global')->defaultFalse()->end()
                                ->end()
                            ->end()
                            ->scalarNode('enable_logger')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addAnonConsumers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('anon_consumer')
            ->children()
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
    }

    protected function addRpcClients(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('rpc_client')
            ->children()
                ->arrayNode('rpc_clients')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->booleanNode('expect_serialized_response')->defaultTrue()->end()
                            ->scalarNode('unserializer')->defaultValue('unserialize')->end()
                            ->booleanNode('lazy')->defaultFalse()->end()
                            ->booleanNode('direct_reply_to')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addRpcServers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('rpc_server')
            ->children()
                ->arrayNode('rpc_servers')
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
                            ->scalarNode('serializer')->defaultValue('serialize')->end()
                            ->scalarNode('enable_logger')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function getExchangeConfiguration()
    {
        $node = new ArrayNodeDefinition('exchange_options');

        return $node
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
            ->end()
        ;
    }

    protected function getQueueConfiguration()
    {
        $node = new ArrayNodeDefinition('queue_options');

        $this->addQueueNodeConfiguration($node);

        return $node;
    }

    protected function getMultipleQueuesConfiguration()
    {
        $node = new ArrayNodeDefinition('queues');
        $prototypeNode = $node->prototype('array');

        $this->addQueueNodeConfiguration($prototypeNode);

        $prototypeNode->children()
            ->scalarNode('callback')->isRequired()->end()
        ->end();

        $prototypeNode->end();

        return $node;
    }

    protected function addQueueNodeConfiguration(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('routing_key')
            ->children()
                ->scalarNode('name')->end()
                ->booleanNode('passive')->defaultFalse()->end()
                ->booleanNode('durable')->defaultTrue()->end()
                ->booleanNode('exclusive')->defaultFalse()->end()
                ->booleanNode('auto_delete')->defaultFalse()->end()
                ->booleanNode('nowait')->defaultFalse()->end()
                ->booleanNode('declare')->defaultTrue()->end()
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
