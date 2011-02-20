<?php

namespace OldSound\RabbitmqBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class RabbitmqExtension extends Extension
{
    public function configLoad($configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, __DIR__.'/../Resources/config');
        $loader->load('rabbitmq.yml');
        
        $config = $this->mergeConfig($configs);
        
        foreach($config['connections'] as $name => $connection)
        {
            $this->loadConnection($connection, $container);
        }
        
        foreach($config['producers'] as $name => $producer)
        {
            $this->loadProducer($producer, $container);
        }
        
        foreach($config['consumers'] as $name => $consumer)
        {
            $this->loadConsumer($consumer, $container);
        }
        
        foreach($config['anon_consumers'] as $name => $consumer)
        {
            $this->loadAnonConsumer($consumer, $container);
        }
        
        foreach($config['rpc_clients'] as $name => $rpc_client)
        {
            $this->loadRpcClient($rpc_client, $container);
        }
        
        foreach($config['rpc_servers'] as $name => $rpc_server)
        {
            $this->loadRpcServer($rpc_server, $container);
        }
    }
    
    protected function mergeConfig(array $configs)
    {
        $mergedConfig = array(
            'connections' => array(),
            'producers' => array(),
            'consumers' => array(),
            'anon_consumers' => array(),
            'rpc_clients' => array(),
            'rpc_servers' => array()
        );
        
        $connectionDefaults = array(
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/'
        );
        
        foreach($configs as $config)
        {
            if(isset($config['connections']))
            {
                foreach($config['connections'] as $name => $connection)
                {
                    if(!isset($mergedConfig['connections'][$name]))
                    {
                        $mergedConfig['connections'][$name] = $connectionDefaults;
                    }
                    $mergedConfig['connections'][$name]['alias'] = $name;
                    foreach($connection as $k => $v)
                    {
                        if(array_key_exists($k, $connectionDefaults))
                        {
                            $mergedConfig['connections'][$name][$k] = $v;
                        }
                    }
                }
            }
            
            $this->mergeItem($mergedConfig, $config, 'producer');
            $this->mergeItem($mergedConfig, $config, 'consumer');
            $this->mergeItem($mergedConfig, $config, 'anon_consumer');
            $this->mergeItem($mergedConfig, $config, 'rpc_client');
            $this->mergeItem($mergedConfig, $config, 'rpc_server');

        }
        
        return $mergedConfig;
    }
    
    protected function mergeItem(&$mergedConfig, $config, $item)
    {
        $clientDefaults = array(
            'connection' => null
        );
        
        if(isset($config[$item . 's']))
        {
            foreach($config[$item . 's'] as $name => $$item)
            {
                if(!isset($mergedConfig[$item . 's'][$name]))
                {
                    $mergedConfig[$item . 's'][$name] = $clientDefaults;
                }
                $mergedConfig[$item . 's'][$name]['alias'] = $name;
                if(!empty($$item))
                {
                    foreach($$item as $k => $v)
                    {
                        $mergedConfig[$item . 's'][$name][$k] = $v;
                    }
                }
            }
        }
    }
    
    protected function loadConnection(array $connection, ContainerBuilder $container)
    {
        $connectionDef = new Definition($container->getParameter('rabbitmq.connection.class'), 
                                        array($connection['host'], $connection['port'],
                                              $connection['user'], $connection['password'],
                                              $connection['vhost']));
        $container->setDefinition(sprintf('rabbitmq.connection.%s', $connection['alias']), $connectionDef);
        
    }
    
    protected function loadProducer(array $producer, ContainerBuilder $container)
    {
        $producerDef = new Definition($container->getParameter('rabbitmq.producer.class'));
        
        $producer = $this->setDefaultItemConnection($producer);
        
        $this->injectConnection($producerDef, $producer);
        
        $producerDef->addMethodCall('setExchangeOptions', array($producer['exchange_options']));
        
        $container->setDefinition(sprintf('rabbitmq.%s_producer', $producer['alias']), $producerDef);
    }
    
    protected function loadConsumer(array $consumer, ContainerBuilder $container)
    {
        $consumerDef = new Definition($container->getParameter('rabbitmq.consumer.class'));
        
        $consumer = $this->setDefaultItemConnection($consumer);
        
        $this->injectConnection($consumerDef, $consumer);
        
        $consumerDef->addMethodCall('setExchangeOptions', array($consumer['exchange_options']));
        $consumerDef->addMethodCall('setQueueOptions', array($consumer['queue_options']));
        
        $container->setDefinition(sprintf('rabbitmq.%s_consumer', $consumer['alias']), $consumerDef);
    }
    
    protected function loadAnonConsumer(array $consumer, ContainerBuilder $container)
    {
        $consumerDef = new Definition($container->getParameter('rabbitmq.anon_consumer.class'));
        
        $consumer = $this->setDefaultItemConnection($consumer);
        
        $this->injectConnection($consumerDef, $consumer);
        
        $consumerDef->addMethodCall('setExchangeOptions', array($consumer['exchange_options']));
        
        $container->setDefinition(sprintf('rabbitmq.%s_anon_consumer', $consumer['alias']), $consumerDef);
    }
    
    protected function loadRpcClient(array $client, ContainerBuilder $container)
    {
        $clientDef = new Definition($container->getParameter('rabbitmq.rpc_client.class'));
        
        $client = $this->setDefaultItemConnection($client);
        
        $this->injectConnection($clientDef, $client);
        
        $clientDef->addMethodCall('initClient');
        
        $container->setDefinition(sprintf('rabbitmq.%s_rpc', $client['alias']), $clientDef);
    }
    
    protected function loadRpcServer(array $server, ContainerBuilder $container)
    {
        $serverDef = new Definition($container->getParameter('rabbitmq.rpc_server.class'));
        
        $server = $this->setDefaultItemConnection($server);
        
        $this->injectConnection($serverDef, $server);
        
        $serverDef->addMethodCall('initServer', array($server['alias']));
        
        $container->setDefinition(sprintf('rabbitmq.%s_server', $server['alias']), $serverDef);
    }
    
    protected function setDefaultItemConnection($item)
    {
        if(null === $item['connection'])
        {
            $item['connection'] = $item['alias'];
        }
        
        return $item;
    }
    
    protected function injectConnection(Definition $def, $item)
    {
        $def->addArgument(new Reference(sprintf('rabbitmq.connection.%s', $item['connection'])));
    }
  
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/';
    }

    public function getNamespace()
    {
        return 'http://www.example.com/symfony/schema/';
    }

    public function getAlias()
    {
        return 'rabbitmq';
    }
}