<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\RabbitMq\DI;

use Kdyby;
use Nette;
use Nette\DI\Compiler;
use Nette\PhpGenerator as Code;
use Nette\DI\Config;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RabbitMqExtension extends Nette\DI\CompilerExtension
{

	const TAG_PRODUCER = 'kdyby.rabbitmq.producer';
	const TAG_CONSUMER = 'kdyby.rabbitmq.consumer';
	const TAG_RPC_CLIENT = 'kdyby.rabbitmq.rpc.client';
	const TAG_RPC_SERVER = 'kdyby.rabbitmq.rpc.server';

	/**
	 * @var array
	 */
	public $defaults = array(
		'connection' => array(),
		'producers' => array(),
		'consumers' => array(),
		'rpcClients' => array(),
		'rpcServers' => array(),
		'debugger' => '%debugMode%',
	);

	/**
	 * @var array
	 */
	public $connectionDefaults = array(
		'host' => '127.0.0.1',
		'port' => 5672,
		'user' => 'guest',
		'password' => 'guest',
		'vhost' => '/',
	);

	/**
	 * @var array
	 */
	public $producersDefaults = array(
		'connection' => 'default',
		'class' => 'Kdyby\RabbitMq\Producer',
		'exchange' => array(),
		'queue' => array(),
		'contentType' => 'text/plain',
		'deliveryMode' => 2,
		'autoSetupFabric' => TRUE,
	);

	/**
	 * @var array
	 */
	public $consumersDefaults = array(
		'connection' => 'default',
		'exchange' => array(),
		'queues' => array(), // for multiple consumers
		'queue' => array(), // for single consumer
		'callback' => NULL,
		'qos' => array(),
		'idleTimeout' => NULL,
		'autoSetupFabric' => TRUE,
	);

	/**
	 * @var array
	 */
	public $rpcClientDefaults = array(
		'connection' => 'default',
		'expectSerializedResponse' => TRUE,
	);

	/**
	 * @var array
	 */
	public $rpcServerDefaults = array(
		'connection' => 'default',
		'callback' => NULL,
		'qos' => array(),
	);

	/**
	 * @var array
	 */
	public $exchangeDefaults = array(
		'passive' => FALSE,
		'durable' => TRUE,
		'autoDelete' => FALSE,
		'internal' => FALSE,
		'nowait' => FALSE,
		'arguments' => NULL,
		'ticket' => NULL,
		'declare' => TRUE,
	);

	/**
	 * @var array
	 */
	public $queueDefaults = array(
		'name' => '',
		'passive' => FALSE,
		'durable' => TRUE,
		'exclusive' => FALSE,
		'autoDelete' => FALSE,
		'nowait' => FALSE,
		'arguments' => NULL,
		'ticket' => NULL,
		'routing_keys' => array(),
	);

	/**
	 * @var array
	 */
	public $qosDefaults = array(
		'prefetchSize' => 0,
		'prefetchCount' => 0,
		'global' => FALSE,
	);

	/**
	 * @var array
	 */
	protected $connectionsMeta = array();



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		if ($unexpected = array_diff(array_keys($config), array_keys($this->defaults))) {
			throw new Nette\Utils\AssertionException("Unexpected key '" . implode("', '", $unexpected) . "' in configuration of {$this->name}.");
		}

		$this->loadConnections($config['connection']);
		$this->loadProducers($config['producers']);
		$this->loadConsumers($config['consumers']);
		$this->loadRpcClients($config['rpcClients']);
		$this->loadRpcServers($config['rpcServers']);

		foreach ($this->connectionsMeta as $name => $meta) {
			$connection = $builder->getDefinition($meta['serviceId']);

			if ($config['debugger']) {
				$builder->addDefinition($panelService = $meta['serviceId'] . '.panel')
					->setClass('Kdyby\RabbitMq\Diagnostics\Panel')
					->setInject(FALSE)
					->setAutowired(FALSE);

				$connection->addSetup('injectPanel', array('@' . $panelService));
			}

			$connection->addSetup('injectServiceLocator');
			$connection->addSetup('injectServiceMap', array(
				$meta['producers'],
				$meta['consumers'],
				$meta['rpcClients'],
				$meta['rpcServers'],
			));
		}

		$this->loadConsole();
	}



	protected function loadConnections($connections)
	{
		$this->connectionsMeta = array(); // reset

		if (isset($connections['user'])) {
			$connections = array('default' => $connections);
		}

		$builder = $this->getContainerBuilder();
		foreach ($connections as $name => $config) {
			$config = $this->mergeConfig($config, $this->connectionDefaults);

			$connection = $builder->addDefinition($serviceName = $this->prefix($name . '.connection'))
				->setClass('Kdyby\RabbitMq\Connection')
				->setInject(FALSE)
				->setArguments(array(
					$config['host'],
					$config['port'],
					$config['user'],
					$config['password'],
					$config['vhost']
				));

			$this->connectionsMeta[$name] = array(
				'serviceId' => $serviceName,
				'producers' => array(),
				'consumers' => array(),
				'rpcClients' => array(),
				'rpcServers' => array(),
			);

			// only the first connection is autowired
			if (count($this->connectionsMeta) > 1) {
				$connection->setAutowired(FALSE);
			}
		}
	}



	protected function loadProducers($producers)
	{
		$builder = $this->getContainerBuilder();

		foreach ($producers as $name => $config) {
			$config = $this->mergeConfig($config, $this->producersDefaults);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new Nette\Utils\AssertionException("Connection {$config['connection']} required in producer {$this->name}.{$name} was not defined.");
			}

			$config['exchange'] = $this->mergeConfig($config['exchange'], $this->exchangeDefaults);
			Nette\Utils\Validators::assertField($config['exchange'], 'name', 'string:3..', "The config item '%' of producer {$this->name}.{$name}");

			$config['queue'] = $this->mergeConfig($config['queue'], $this->queueDefaults);
			Nette\Utils\Validators::assertField($config['queue'], 'name', 'string:3..', "The config item '%' of producer {$this->name}.{$name}");

			$producer = $builder->addDefinition($serviceName = $this->prefix('producer.' . $name))
				->setFactory($config['class'], array('@' . $this->connectionsMeta[$config['connection']]['serviceId']))
				->setClass('Kdyby\RabbitMq\IProducer')
				->addSetup('setExchangeOptions', array($config['exchange']))
				->addSetup('setQueueOptions', array($config['queue']))
				->addSetup('setContentType', array($config['contentType']))
				->addSetup('setDeliveryMode', array($config['deliveryMode']))
				->addTag(self::TAG_PRODUCER);

			if (!$config['autoSetupFabric']) {
				$producer->addSetup('disableAutoSetupFabric');
			}

			$this->connectionsMeta[$config['connection']]['producers'][$name] = $serviceName;
		}
	}



	protected function loadConsumers($consumers)
	{
		$builder = $this->getContainerBuilder();

		foreach ($consumers as $name => $config) {
			$config = $this->mergeConfig($config, $this->consumersDefaults);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new Nette\Utils\AssertionException("Connection {$config['connection']} required in consumer {$this->name}.{$name} was not defined.");
			}

			$consumer = $builder->addDefinition($serviceName = $this->prefix('consumer.' . $name))
				->addSetup('setExchangeOptions', array($this->mergeConfig($config['exchange'], $this->exchangeDefaults)))
				->addTag(self::TAG_CONSUMER)
				->setAutowired(FALSE);

			if (!empty($config['queues']) && empty($config['queue'])) {
				foreach ($config['queues'] as $queueName => $queueConfig) {
					$queueConfig['name'] = $queueName;
					$config['queues'][$queueName] = $this->mergeConfig($queueConfig, $this->queueDefaults);
				}

				$consumer
					->setClass('Kdyby\RabbitMq\MultipleConsumer')
					->addSetup('setQueues', array($config['queues']));

			} elseif (empty($config['queues']) && !empty($config['queue'])) {
				$consumer
					->setClass('Kdyby\RabbitMq\Consumer')
					->addSetup('setQueueOptions', array($this->mergeConfig($config['queue'], $this->queueDefaults)))
					->addSetup('setCallback', array($config['callback']));

			} else {
				$consumer
					->setClass('Kdyby\RabbitMq\AnonymousConsumer')
					->addSetup('setCallback', array($config['callback']));
			}

			$consumer->setArguments(array('@' . $this->connectionsMeta[$config['connection']]['serviceId']));

			if (array_filter($config['qos'])) { // has values
				$config['qos'] = $this->mergeConfig($config['qos'], $this->qosDefaults);
				$consumer->addSetup('setQosOptions', array(
					$config['qos']['prefetchSize'],
					$config['qos']['prefetchCount'],
					$config['qos']['global'],
				));
			}

			if ($config['idleTimeout']) {
				$consumer->addSetup('setIdleTimeout', array($config['idleTimeout']));
			}

			if (!$config['autoSetupFabric']) {
				$consumer->addSetup('disableAutoSetupFabric');
			}

			$this->connectionsMeta[$config['connection']]['consumers'][$name] = $serviceName;
		}
	}



	protected function loadRpcClients($clients)
	{
		$builder = $this->getContainerBuilder();

		foreach ($clients as $name => $config) {
			$config = $this->mergeConfig($config, $this->rpcClientDefaults);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new Nette\Utils\AssertionException("Connection {$config['connection']} required in rpc client {$this->name}.{$name} was not defined.");
			}

			$builder->addDefinition($serviceName = $this->prefix('rpcClient.' . $name))
				->setClass('Kdyby\RabbitMq\RpcClient', array('@' . $this->connectionsMeta[$config['connection']]['serviceId']))
				->addSetup('initClient', array($config['expectSerializedResponse']))
				->addTag(self::TAG_RPC_CLIENT)
				->setAutowired(FALSE);

			$this->connectionsMeta[$config['connection']]['rpcClients'][$name] = $serviceName;
		}
	}



	protected function loadRpcServers($servers)
	{
		$builder = $this->getContainerBuilder();

		foreach ($servers as $name => $config) {
			$config = $this->mergeConfig($config, $this->rpcServerDefaults);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new Nette\Utils\AssertionException("Connection {$config['connection']} required in rpc server {$this->name}.{$name} was not defined.");
			}


			$rpcServer = $builder->addDefinition($serviceName = $this->prefix('rpcServer.' . $name))
				->setClass('Kdyby\RabbitMq\RpcServer', array('@' . $this->connectionsMeta[$config['connection']]['serviceId']))
				->addSetup('initServer', array($name))
				->addSetup('setCallback', array($config['callback']))
				->addTag(self::TAG_RPC_SERVER)
				->setAutowired(FALSE);

			if (array_filter($config['qos'])) { // has values
				$config['qos'] = $this->mergeConfig($config['qos'], $this->qosDefaults);
				$rpcServer->addSetup('setQosOptions', array(
					$config['qos']['prefetchSize'],
					$config['qos']['prefetchCount'],
					$config['qos']['global'],
				));
			}

			$this->connectionsMeta[$config['connection']]['rpcSevers'][$name] = $serviceName;
		}
	}



	private function loadConsole()
	{
		if (!class_exists('Kdyby\Console\DI\ConsoleExtension') || PHP_SAPI !== 'cli') {
			return;
		}

		$builder = $this->getContainerBuilder();

		foreach (array(
			'Kdyby\RabbitMq\Command\ConsumerCommand',
			'Kdyby\RabbitMq\Command\PurgeConsumerCommand',
			'Kdyby\RabbitMq\Command\RpcServerCommand',
			'Kdyby\RabbitMq\Command\SetupFabricCommand',
			'Kdyby\RabbitMq\Command\StdInProducerCommand',
		) as $i => $class) {
			$builder->addDefinition($this->prefix('console.' . $i))
				->setClass($class)
				->addTag(Kdyby\Console\DI\ConsoleExtension::COMMAND_TAG);
		}
	}



	protected function mergeConfig($config, $defaults)
	{
		return Config\Helpers::merge($config, $this->compiler->getContainerBuilder()->expand($defaults));
	}



	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Compiler $compiler) {
			$compiler->addExtension('rabbitmq', new RabbitMqExtension());
		};
	}

}

