<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPLazyConnection;

/**
 * @property array $exchangeOptions
 * @property array $queueOptions
 */
abstract class AmqpMember
{

	use \Nette\SmartObject;

	/**
	 * @var \Kdyby\RabbitMq\Connection
	 */
	protected $conn;

	/**
	 * @var \Kdyby\RabbitMq\Channel
	 */
	protected $ch;

	/**
	 * @var string
	 */
	protected $consumerTag;

	/**
	 * @var string
	 */
	protected $routingKey = '';

	/**
	 * @var bool
	 */
	protected $autoSetupFabric = TRUE;

	/**
	 * @var array
	 */
	protected $basicProperties = [
		'content_type' => 'text/plain',
		'delivery_mode' => 2,
	];

	/**
	 * @var array
	 */
	protected $exchangeOptions = [
		'name' => NULL,
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
	 * @var bool
	 */
	protected $exchangeDeclared = FALSE;

	/**
	 * @var array
	 */
	protected $queueOptions = [
		'name' => '',
		'passive' => FALSE,
		'durable' => TRUE,
		'exclusive' => FALSE,
		'autoDelete' => FALSE,
		'nowait' => FALSE,
		'arguments' => NULL,
		'ticket' => NULL,
		'routing_keys' => [],
	];

	/**
	 * @var bool
	 */
	protected $queueDeclared = FALSE;

	public function __construct(Connection $conn, ?string $consumerTag = NULL)
	{
		$this->conn = $conn;
		$this->consumerTag = empty($consumerTag) ? \sprintf('PHPPROCESS_%s_%s', \gethostname(), \getmypid()) : $consumerTag;

		if (!($conn instanceof AMQPLazyConnection)) {
			$this->getChannel();
		}
	}

	public function __destruct()
	{
		if ($this->ch) {
			$this->ch->close();
		}

		if ($this->conn->isConnected()) {
			$this->conn->close();
		}
	}

	public function getChannel(): AMQPChannel
	{
		if (empty($this->ch)) {
			$this->ch = $this->conn->channel();
		}

		return $this->ch;
	}

	public function setChannel(AMQPChannel $ch): void
	{
		$this->ch = $ch;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @param  array<mixed> $options
	 * @return void
	 */
	public function setExchangeOptions(array $options = []): void
	{
		if (!isset($options['name'])) {
			throw new \InvalidArgumentException('You must provide an exchange name');
		}

		if (empty($options['type'])) {
			throw new \InvalidArgumentException('You must provide an exchange type');
		}

		$this->exchangeOptions = $options + $this->exchangeOptions;
	}

	/**
	 * @return array<mixed>
	 */
	public function getExchangeOptions(): array
	{
		return $this->exchangeOptions;
	}

	/**
	 * @param  array<mixed> $options
	 * @return void
	 */
	public function setQueueOptions(array $options = []): void
	{
		$this->queueOptions = $options + $this->queueOptions;
	}

	/**
	 * @return array<mixed>
	 */
	public function getQueueOptions(): array
	{
		return $this->queueOptions;
	}

	public function setRoutingKey(string $routingKey): void
	{
		$this->routingKey = $routingKey;
	}

	protected function exchangeDeclare(): void
	{
		if (empty($this->exchangeOptions['declare']) || empty($this->exchangeOptions['name'])) {
			return;
		}

		$this->getChannel()->exchange_declare(
			$this->exchangeOptions['name'],
			$this->exchangeOptions['type'],
			$this->exchangeOptions['passive'],
			$this->exchangeOptions['durable'],
			$this->exchangeOptions['autoDelete'],
			$this->exchangeOptions['internal'],
			$this->exchangeOptions['nowait'],
			$this->exchangeOptions['arguments'],
			$this->exchangeOptions['ticket']
		);

		$this->exchangeDeclared = TRUE;
	}

	protected function queueDeclare(): void
	{
		if (empty($this->queueOptions['name'])) {
			return;
		}

		$this->doQueueDeclare($this->queueOptions['name'], $this->queueOptions);
		$this->queueDeclared = TRUE;
	}

	/**
	 * @param string $name
	 * @param array<mixed> $options
	 */
	protected function doQueueDeclare(string $name, array $options): void
	{
		[$queueName ] = $this->getChannel()->queue_declare(
			$name,
			$options['passive'],
			$options['durable'],
			$options['exclusive'],
			$options['autoDelete'],
			$options['nowait'],
			$options['arguments'],
			$options['ticket']
		);

		if (empty($options['routing_keys'])) {
			if (!empty($this->exchangeOptions['name'])) {
				$this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);
			}

		} else {
			foreach ($options['routing_keys'] as $routingKey) {
				$this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $routingKey);
			}
		}
	}

	public function setupFabric(): void
	{
		if (!$this->exchangeDeclared) {
			$this->exchangeDeclare();
		}

		if (!$this->queueDeclared) {
			$this->queueDeclare();
		}
	}

	/**
	 * disables the automatic SetupFabric when using a consumer or producer
	 */
	public function disableAutoSetupFabric(): void
	{
		$this->autoSetupFabric = FALSE;
	}

}
