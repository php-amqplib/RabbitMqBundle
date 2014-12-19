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

        $rootNode = $tree->root('old_sound_rabbit_mq');

        $rootNode
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('enable_collector')->defaultValue(false)->end()
                ->booleanNode('sandbox')->defaultValue(false)->end()
            ->end()
        ;

        $this->addConnections($rootNode);
        $this->addProducers($rootNode);
        $this->addConsumers($rootNode);
        $this->addMultipleConsumers($rootNode);
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
                            ->scalarNode('host')->defaultValue('localhost')->end()
                            ->scalarNode('port')->defaultValue(5672)->end()
                            ->scalarNode('user')->defaultValue('guest')->end()
                            ->scalarNode('password')->defaultValue('guest')->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                            ->booleanNode('lazy')->defaultFalse()->end()
                            ->booleanNode('ssl')->defaultFalse()->end()
                            ->arrayNode('ssl_options')
                                ->children()
                                    ->scalarNode('verify_peer')->end()
                                    ->scalarNode('allow_self_signed')->end()
                                    ->scalarNode('cafile')->end()
                                    ->scalarNode('capath')->end()
                                    ->scalarNode('local_cert')->end()
                                    ->scalarNode('passphrase')->end()
                                    ->scalarNode('CN_match')->end()
                                    ->scalarNode('verify_depth')->end()
                                    ->scalarNode('ciphers')->end()
                                    ->scalarNode('capture_peer_cert')->end()
                                    ->scalarNode('capture_peer_cert_chain')->end()
                                    ->scalarNode('SNI_enabled')->end()
                                    ->scalarNode('SNI_server_name')->end()
                                    ->scalarNode('disable_compression')->end()
                                ->end()
                            ->end()
                            ->scalarNode('connection_timeout')->defaultValue(3)->end()
                            ->scalarNode('read_write_timeout')->defaultValue(3)->end()
                            ->booleanNode('keepalive')->defaultFalse()->info('requires php-amqplib v2.4.1+ and PHP5.4+')->end()
                            ->scalarNode('heartbeat')->defaultValue(0)->info('requires php-amqplib v2.4.1+')->end()
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
            ->children()
                ->scalarNode('name')->isRequired()->end()
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
