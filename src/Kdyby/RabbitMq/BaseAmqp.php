<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;



abstract class BaseAmqp
{

	/**
	 * @var \PhpAmqpLib\Connection\AMQPConnection
	 */
	protected $conn;

	/**
	 * @var \PhpAmqpLib\Channel\AMQPChannel
	 */
	protected $ch;

	/**
	 * @var string
	 */
	protected $consumerTag;

	/**
	 * @var bool
	 */
	protected $exchangeDeclared = false;

	/**
	 * @var bool
	 */
	protected $queueDeclared = false;

	/**
	 * @var string
	 */
	protected $routingKey = '';

	/**
	 * @var bool
	 */
	protected $autoSetupFabric = true;

	/**
	 * @var array
	 */
	protected $basicProperties = array(
		'content_type' => 'text/plain',
		'delivery_mode' => 2
	);

	/**
	 * @var array
	 */
	protected $exchangeOptions = array(
		'passive' => false,
		'durable' => true,
		'auto_delete' => false,
		'internal' => false,
		'nowait' => false,
		'arguments' => null,
		'ticket' => null,
		'declare' => true,
	);

	/**
	 * @var array
	 */
	protected $queueOptions = array(
		'name' => '',
		'passive' => false,
		'durable' => true,
		'exclusive' => false,
		'auto_delete' => false,
		'nowait' => false,
		'arguments' => null,
		'ticket' => null,
		'routing_keys' => array(),
	);



	/**
	 * @param AMQPConnection $conn
	 * @param AMQPChannel|null $ch
	 * @param string $consumerTag
	 */
	public function __construct(AMQPConnection $conn, AMQPChannel $ch = null, $consumerTag = null)
	{
		$this->conn = $conn;
		$this->ch = $ch;

		if (!($conn instanceof AMQPLazyConnection)) {
			$this->getChannel();
		}

		$this->consumerTag = empty($consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $consumerTag;
	}



	public function __destruct()
	{
		//TODO FIX!
		// if (!empty($this->getChannel()) && !empty($this->conn))
		// {
		//     $this->getChannel()->close();
		// }
		//
		// if (!empty($this->conn))
		// {
		//     $this->conn->close();
		// }
	}



	/**
	 * @return AMQPChannel
	 */
	public function getChannel()
	{
		if (empty($this->ch)) {
			$this->ch = $this->conn->channel();
		}

		return $this->ch;
	}



	/**
	 * @param  AMQPChannel $ch
	 * @return void
	 */
	public function setChannel(AMQPChannel $ch)
	{
		$this->ch = $ch;
	}



	/**
	 * @throws \InvalidArgumentException
	 * @param  array $options
	 * @return void
	 */
	public function setExchangeOptions(array $options = array())
	{
		if (!isset($options['name'])) {
			throw new \InvalidArgumentException('You must provide an exchange name');
		}

		if (empty($options['type'])) {
			throw new \InvalidArgumentException('You must provide an exchange type');
		}

		$this->exchangeOptions = array_merge($this->exchangeOptions, $options);
	}



	/**
	 * @param  array $options
	 * @return void
	 */
	public function setQueueOptions(array $options = array())
	{
		$this->queueOptions = array_merge($this->queueOptions, $options);
	}



	/**
	 * @param  string $routingKey
	 * @return void
	 */
	public function setRoutingKey($routingKey)
	{
		$this->routingKey = $routingKey;
	}



	protected function exchangeDeclare()
	{
		if (empty($this->exchangeOptions['declare'])) {
			return;
		}

		$this->getChannel()->exchange_declare(
			$this->exchangeOptions['name'],
			$this->exchangeOptions['type'],
			$this->exchangeOptions['passive'],
			$this->exchangeOptions['durable'],
			$this->exchangeOptions['auto_delete'],
			$this->exchangeOptions['internal'],
			$this->exchangeOptions['nowait'],
			$this->exchangeOptions['arguments'],
			$this->exchangeOptions['ticket']);

		$this->exchangeDeclared = true;
	}



	protected function queueDeclare()
	{
		if (empty($this->queueOptions['name'])) {
			return;
		}

		list($queueName, ,) = $this->getChannel()->queue_declare(
			$this->queueOptions['name'], $this->queueOptions['passive'],
			$this->queueOptions['durable'], $this->queueOptions['exclusive'],
			$this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
			$this->queueOptions['arguments'], $this->queueOptions['ticket']
		);

		if (empty($this->queueOptions['routing_keys'])) {
			$this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);

		} else {
			foreach ($this->queueOptions['routing_keys'] as $routingKey) {
				$this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $routingKey);
			}
		}

		$this->queueDeclared = true;
	}



	public function setupFabric()
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
	public function disableAutoSetupFabric()
	{
		$this->autoSetupFabric = false;
	}
}
