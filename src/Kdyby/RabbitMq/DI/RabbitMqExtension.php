<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\RabbitMq\DI;

use Nette;
use Nette\DI\Config;
use Nette\Utils\Validators;

class RabbitMqExtension extends \Nette\DI\CompilerExtension
{

	public const TAG_COMMAND_KDYBY = 'kdyby.console.command';
	public const TAG_COMMAND = 'console.command';
	public const TAG_PRODUCER = 'kdyby.rabbitmq.producer';
	public const TAG_CONSUMER = 'kdyby.rabbitmq.consumer';
	public const TAG_RPC_CLIENT = 'kdyby.rabbitmq.rpc.client';
	public const TAG_RPC_SERVER = 'kdyby.rabbitmq.rpc.server';
	public const EXTENDS_KEY = '_extends';

	/**
	 * @var array
	 */
	public $defaults = [
		'connection' => [],
		'producers' => [],
		'consumers' => [],
		'rpcClients' => [],
		'rpcServers' => [],
		'debugger' => '%debugMode%',
		'autoSetupFabric' => '%debugMode%',
	];

	/**
	 * @var array
	 */
	public $connectionDefaults = [
		'host' => '127.0.0.1',
		'port' => 5672,
		'user' => NULL,
		'password' => NULL,
		'vhost' => '/',
	];

	/**
	 * @var array
	 */
	public $producersDefaults = [
		'connection' => 'default',
		'class' => \Kdyby\RabbitMq\Producer::class,
		'exchange' => [],
		'queue' => [],
		'contentType' => 'text/plain',
		'deliveryMode' => 2,
		'routingKey' => '',
		'autoSetupFabric' => NULL, // inherits from `rabbitmq: autoSetupFabric:`
	];

	/**
	 * @var array
	 */
	public $consumersDefaults = [
		'connection' => 'default',
		'exchange' => [],
		'queues' => [], // for multiple consumers
		'queue' => [], // for single consumer
		'callback' => NULL,
		'qos' => [],
		'idleTimeout' => NULL,
		'autoSetupFabric' => NULL, // inherits from `rabbitmq: autoSetupFabric:`
	];

	/**
	 * @var array
	 */
	public $rpcClientDefaults = [
		'connection' => 'default',
		'expectSerializedResponse' => TRUE,
	];

	/**
	 * @var array
	 */
	public $rpcServerDefaults = [
		'connection' => 'default',
		'callback' => NULL,
		'qos' => [],
	];

	/**
	 * @var array
	 */
	public $exchangeDefaults = [
		'passive' => FALSE,
		'durable' => TRUE,
		'autoDelete' => FALSE,
		'internal' => FALSE,
		'nowait' => FALSE,
		'arguments' => NULL,
		'ticket' => NULL,
		'declare' => TRUE,
	];

	/**
	 * @var array
	 */
	public $queueDefaults = [
		'name' => '',
		'passive' => FALSE,
		'durable' => TRUE,
		'noLocal' => FALSE,
		'noAck' => FALSE,
		'exclusive' => FALSE,
		'autoDelete' => FALSE,
		'nowait' => FALSE,
		'arguments' => NULL,
		'ticket' => NULL,
		'routing_keys' => [],
	];

	/**
	 * @var array
	 */
	public $qosDefaults = [
		'prefetchSize' => 0,
		'prefetchCount' => 0,
		'global' => FALSE,
	];

	/**
	 * @var array
	 */
	protected $connectionsMeta = [];

	/**
	 * @var array
	 */
	private $producersConfig = [];

	/**
	 * @throws \Nette\Utils\AssertionException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = \Nette\DI\Config\Helpers::merge($this->getConfig(), $this->defaults);

		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof IProducersProvider) {
				$producers = $extension->getRabbitProducers();
				Validators::assert($producers, 'array:1..');
				$config['producers'] = \array_merge($config['producers'], $producers);
			}
			if ($extension instanceof IConsumersProvider) {
				$consumers = $extension->getRabbitConsumers();
				Validators::assert($consumers, 'array:1..');
				$config['consumers'] = \array_merge($config['consumers'], $consumers);
			}
			if ($extension instanceof IRpcClientsProvider) {
				$rpcClients = $extension->getRabbitRpcClients();
				Validators::assert($rpcClients, 'array:1..');
				$config['rpcClients'] = \array_merge($config['rpcClients'], $rpcClients);
			}
			if ($extension instanceof IRpcServersProvider) {
				$rpcServers = $extension->getRabbitRpcServers();
				Validators::assert($rpcServers, 'array:1..');
				$config['rpcServers'] = \array_merge($config['rpcServers'], $rpcServers);
			}
		}

		$unexpected = \array_diff(\array_keys($config), \array_keys($this->defaults));
		if ($unexpected) {
			throw new \Nette\Utils\AssertionException(
				\sprintf(
					'Unexpected key \'%s\' in configuration of %s.',
					\implode("', '", $unexpected),
					$this->name
				)
			);
		}

		$builder->parameters[$this->name] = $config;

		$this->loadConnections($config['connection']);
		$this->loadProducers($config['producers']);
		$this->loadConsumers($config['consumers']);
		$this->loadRpcClients($config['rpcClients']);
		$this->loadRpcServers($config['rpcServers']);

		foreach ($this->connectionsMeta as $meta) {
			/** @var \Nette\DI\Definitions\ServiceDefinition $connection */
			$connection = $builder->getDefinition($meta['serviceId']);

			if ($config['debugger']) {
				$builder->addDefinition($panelService = $meta['serviceId'] . '.panel')
					->setType(\Kdyby\RabbitMq\Diagnostics\Panel::class)
					->addSetup('injectServiceMap', [
						$meta['consumers'],
						$meta['rpcServers'],
					])
					->addTag(\Nette\DI\Extensions\InjectExtension::TAG_INJECT, FALSE)
					->setAutowired(FALSE);

				$connection->addSetup('injectPanel', ['@' . $panelService]);
			}

			$connection->addSetup('injectServiceLocator');
			$connection->addSetup('injectServiceMap', [
				$meta['producers'],
				$meta['consumers'],
				$meta['rpcClients'],
				$meta['rpcServers'],
			]);
		}

		$this->loadConsole();
	}

	public function beforeCompile(): void
	{
		unset($this->getContainerBuilder()->parameters[$this->name]);
	}

	/**
	 * @param array<mixed> $connections
	 * @throws \Nette\Utils\AssertionException
	 */
	protected function loadConnections(array $connections): void
	{
		$this->connectionsMeta = []; // reset

		if (isset($connections['user'])) {
			$connections = ['default' => $connections];
		}

		$builder = $this->getContainerBuilder();
		foreach ($connections as $name => $config) {
			$config = $this->mergeConfig($config, $this->connectionDefaults);

			Nette\Utils\Validators::assertField(
				$config,
				'user',
				'string:3..',
				\sprintf(
					'The config item \'%%\' of connection %s.%s',
					$this->name,
					$name
				)
			);
			Nette\Utils\Validators::assertField(
				$config,
				'password',
				'string:3..',
				\sprintf(
					'The config item \'%%\' of connection %s.%s',
					$this->name,
					$name
				)
			);

			$connection = $builder->addDefinition($serviceName = $this->prefix($name . '.connection'))
				->setType(\Kdyby\RabbitMq\Connection::class)
				->setArguments([
					$config['host'],
					$config['port'],
					$config['user'],
					$config['password'],
					$config['vhost'],
				]);

			$this->connectionsMeta[$name] = [
				'serviceId' => $serviceName,
				'producers' => [],
				'consumers' => [],
				'rpcClients' => [],
				'rpcServers' => [],
			];

			// only the first connection is autowired
			if (\count($this->connectionsMeta) > 1) {
				$connection->setAutowired(FALSE);
			}
		}
	}

	/**
	 * @param array<mixed> $producers
	 * @throws \Nette\Utils\AssertionException
	 */
	protected function loadProducers(array $producers): void
	{
		$builder = $this->getContainerBuilder();

		foreach ($producers as $name => $config) {
			$config = $this->mergeConfig($config, ['autoSetupFabric' => $builder->parameters[$this->name]['autoSetupFabric']] + $this->producersDefaults);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new \Nette\Utils\AssertionException(
					\sprintf(
						'Connection %s required in producer %s.%s was not defined.',
						$config['connection'],
						$this->name,
						$name
					)
				);
			}

			$producer = $builder->addDefinition($serviceName = $this->prefix('producer.' . $name))
				->setFactory($config['class'], ['@' . $this->connectionsMeta[$config['connection']]['serviceId']])
				->setType(\Kdyby\RabbitMq\IProducer::class)
				->addSetup('setContentType', [$config['contentType']])
				->addSetup('setDeliveryMode', [$config['deliveryMode']])
				->addSetup('setRoutingKey', [$config['routingKey']])
				->addTag(self::TAG_PRODUCER);

			if (!empty($config['exchange'])) {
				$config['exchange'] = $this->mergeConfig($config['exchange'], $this->exchangeDefaults);
				Nette\Utils\Validators::assertField(
					$config['exchange'],
					'name',
					'string:3..',
					\sprintf(
						'The config item \'exchange.%%\' of producer %s.%s',
						$this->name,
						$name
					)
				);
				Nette\Utils\Validators::assertField(
					$config['exchange'],
					'type',
					'string:3..',
					\sprintf(
						"The config item 'exchange.%%' of producer %s.%s",
						$this->name,
						$name
					)
				);
				$producer->addSetup('setExchangeOptions', [$config['exchange']]);
			}

			$config['queue'] = $this->mergeConfig($config['queue'], $this->queueDefaults);
			$producer->addSetup('setQueueOptions', [$config['queue']]);

			if ($config['autoSetupFabric'] === FALSE) {
				$producer->addSetup('disableAutoSetupFabric');
			}

			$this->connectionsMeta[$config['connection']]['producers'][$name] = $serviceName;
			$this->producersConfig[$name] = $config;
		}
	}

	/**
	 * @param array<mixed> $consumers
	 * @throws \Nette\Utils\AssertionException
	 */
	protected function loadConsumers(array $consumers): void
	{
		$builder = $this->getContainerBuilder();

		foreach ($consumers as $name => $config) {
			$config = $this->mergeConfig($config, ['autoSetupFabric' => $builder->parameters[$this->name]['autoSetupFabric']] + $this->consumersDefaults);
			$config = $this->extendConsumerFromProducer($name, $config);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new \Nette\Utils\AssertionException(
					\sprintf(
						'Connection %s required in consumer %s.%s was not defined.',
						$config['connection'],
						$this->name,
						$name
					)
				);
			}

			$consumer = $builder->addDefinition($serviceName = $this->prefix('consumer.' . $name))
				->addTag(self::TAG_CONSUMER)
				->setAutowired(FALSE);

			if (!empty($config['exchange'])) {
				Nette\Utils\Validators::assertField(
					$config['exchange'],
					'name',
					'string:3..',
					\sprintf(
						'The config item \'exchange.%%\' of consumer %s.%s',
						$this->name,
						$name
					)
				);
				Nette\Utils\Validators::assertField(
					$config['exchange'],
					'type',
					'string:3..',
					\sprintf('The config item \'exchange.%%\' of consumer %s.%s', $this->name, $name)
				);
				$consumer->addSetup('setExchangeOptions', [$this->mergeConfig($config['exchange'], $this->exchangeDefaults)]);
			}

			if (!empty($config['queues']) && empty($config['queue'])) {
				foreach ($config['queues'] as $queueName => $queueConfig) {
					$queueConfig['name'] = $queueName;
					$config['queues'][$queueName] = $this->mergeConfig($queueConfig, $this->queueDefaults);

					if (isset($queueConfig['callback'])) {
						$config['queues'][$queueName]['callback'] = self::fixCallback($queueConfig['callback']);
					}
				}

				$consumer
					->setType(\Kdyby\RabbitMq\MultipleConsumer::class)
					->addSetup('setQueues', [$config['queues']]);

			} elseif (empty($config['queues']) && !empty($config['queue'])) {
				$consumer
					->setType(\Kdyby\RabbitMq\Consumer::class)
					->addSetup('setQueueOptions', [$this->mergeConfig($config['queue'], $this->queueDefaults)])
					->addSetup('setCallback', [self::fixCallback($config['callback'])]);

			} else {
				$consumer
					->setType(\Kdyby\RabbitMq\AnonymousConsumer::class)
					->addSetup('setCallback', [self::fixCallback($config['callback'])]);
			}

			$consumer->setArguments(['@' . $this->connectionsMeta[$config['connection']]['serviceId']]);

			if (\array_filter($config['qos'])) { // has values
				$config['qos'] = $this->mergeConfig($config['qos'], $this->qosDefaults);
				$consumer->addSetup('setQosOptions', [
					$config['qos']['prefetchSize'],
					$config['qos']['prefetchCount'],
					$config['qos']['global'],
				]);
			}

			if ($config['idleTimeout']) {
				$consumer->addSetup('setIdleTimeout', [$config['idleTimeout']]);
			}

			if ($config['autoSetupFabric'] === FALSE) {
				$consumer->addSetup('disableAutoSetupFabric');
			}

			$this->connectionsMeta[$config['connection']]['consumers'][$name] = $serviceName;
		}
	}

	/**
	 * @param string $consumerName
	 * @param array<mixed> $config
	 * @return array<mixed>
	 * @throws \Nette\Utils\AssertionException
	 */
	private function extendConsumerFromProducer(string &$consumerName, array $config): array
	{
		$m = Nette\Utils\Strings::match(
			$consumerName,
			'~^(?P<consumerName>[^>\s]+)\s*\<\s*(?P<producerName>[^>\s]+)\z~'
		);
		if (isset($config[self::EXTENDS_KEY])) {
			$producerName = $config[self::EXTENDS_KEY];

		} elseif ($m) {
			$consumerName = $m['consumerName'];
			$producerName = $m['producerName'];

		} else {
			return $config;
		}

		if ( ! isset($this->producersConfig[$producerName])) {
			throw new \Nette\Utils\AssertionException(
				\sprintf(
					'Consumer %s.%s cannot extend unknown producer %s.%s.',
					$this->name,
					$consumerName,
					$this->name,
					$producerName
				)
			);
		}
		$producerConfig = $this->producersConfig[$producerName];

		if (!empty($producerConfig['exchange'])) {
			$config['exchange'] = $this->mergeConfig($config['exchange'], $producerConfig['exchange']);
		}

		if (empty($config['queues']) && !empty($producerConfig['queue'])) {
			$config['queue'] = $this->mergeConfig($config['queue'], $producerConfig['queue']);
		}

		return $config;
	}

	/**
	 * @param array<mixed> $clients
	 * @throws \Nette\Utils\AssertionException
	 */
	protected function loadRpcClients(array $clients): void
	{
		$builder = $this->getContainerBuilder();

		foreach ($clients as $name => $config) {
			$config = $this->mergeConfig($config, $this->rpcClientDefaults);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new \Nette\Utils\AssertionException(
					\sprintf(
						'Connection %s required in rpc client %s.%s was not defined.',
						$config['connection'],
						$this->name,
						$name
					)
				);
			}

			$builder->addDefinition($serviceName = $this->prefix('rpcClient.' . $name))
				->setType(\Kdyby\RabbitMq\RpcClient::class)
				->setArguments([
					'@' . $this->connectionsMeta[$config['connection']]['serviceId'],
				])
				->addSetup('initClient', [$config['expectSerializedResponse']])
				->addTag(self::TAG_RPC_CLIENT)
				->setAutowired(FALSE);

			$this->connectionsMeta[$config['connection']]['rpcClients'][$name] = $serviceName;
		}
	}

	/**
	 * @param array<mixed> $servers
	 * @throws \Nette\Utils\AssertionException
	 */
	protected function loadRpcServers(array $servers): void
	{
		$builder = $this->getContainerBuilder();

		foreach ($servers as $name => $config) {
			$config = $this->mergeConfig($config, $this->rpcServerDefaults);

			if (!isset($this->connectionsMeta[$config['connection']])) {
				throw new \Nette\Utils\AssertionException(
					\sprintf(
						'Connection %s required in rpc server %s.%s was not defined.',
						$config['connection'],
						$this->name,
						$name
					)
				);
			}

			$rpcServer = $builder->addDefinition($serviceName = $this->prefix('rpcServer.' . $name))
				->setType(\Kdyby\RabbitMq\RpcServer::class)
				->setArguments(['@' . $this->connectionsMeta[$config['connection']]['serviceId']])
				->addSetup('initServer', [$name])
				->addSetup('setCallback', [self::fixCallback($config['callback'])])
				->addTag(self::TAG_RPC_SERVER)
				->setAutowired(FALSE);

			if (\array_filter($config['qos'])) { // has values
				$config['qos'] = $this->mergeConfig($config['qos'], $this->qosDefaults);
				$rpcServer->addSetup('setQosOptions', [
					$config['qos']['prefetchSize'],
					$config['qos']['prefetchCount'],
					$config['qos']['global'],
				]);
			}

			$this->connectionsMeta[$config['connection']]['rpcServers'][$name] = $serviceName;
		}
	}

	private function loadConsole(): void
	{
		if (!\class_exists(\Symfony\Component\Console\Command\Command::class) || PHP_SAPI !== 'cli') {
			return;
		}

		$builder = $this->getContainerBuilder();

		foreach ([
			\Kdyby\RabbitMq\Command\ConsumerCommand::class,
			\Kdyby\RabbitMq\Command\PurgeConsumerCommand::class,
			\Kdyby\RabbitMq\Command\RpcServerCommand::class,
			\Kdyby\RabbitMq\Command\SetupFabricCommand::class,
			\Kdyby\RabbitMq\Command\StdInProducerCommand::class,
		] as $i => $class) {
			$builder->addDefinition($this->prefix('console.' . $i))
				->setType($class)
				->addTag(self::TAG_COMMAND_KDYBY)
				->addTag(self::TAG_COMMAND);
		}
	}

	/**
	 * @param array<mixed>|string $config
	 * @param array<mixed>|string $defaults
	 * @return array<mixed>|string
	 */
	protected function mergeConfig($config, $defaults)
	{
		return Config\Helpers::merge(
			$config,
			\Nette\DI\Helpers::expand($defaults, $this->compiler->getContainerBuilder()->parameters)
		);
	}

	/**
	 * @param string|mixed $callback
	 * @return string|mixed
	 */
	protected static function fixCallback($callback)
	{
		[$callback] = self::filterArgs($callback);
		if ($callback instanceof Nette\DI\Statement && \substr_count($callback->entity, '::') && empty($callback->arguments)) {
			$callback = \explode('::', $callback->entity, 2);
		}

		return $callback;
	}

	/**
	 * @param string|mixed $statement
	 * @return \Nette\DI\Statement[]
	 */
	protected static function filterArgs($statement): array
	{
		return Nette\DI\Helpers::filterArguments([
			\is_string($statement) ? new Nette\DI\Statement($statement) : $statement,
		]);
	}

}
