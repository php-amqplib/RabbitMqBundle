<?php

namespace Kdyby\RabbitMq;

use Nette\SmartObject;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPLazyConnection;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property array $exchangeOptions
 * @property array $queueOptions
 */
abstract class AmqpMember
{
    use SmartObject;

	/**
	 * @var Connection
	 */
	protected $conn;

	/**
	 * @var Channel
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
	protected $autoSetupFabric = true;

	/**
	 * @var array
	 */
	protected $basicProperties = [
		'content_type' => 'text/plain',
		'delivery_mode' => 2
	];

	/**
	 * @var array
	 */
	protected $exchangeOptions = [
		'name' => NULL,
		'passive' => false,
		'durable' => true,
		'autoDelete' => false,
		'internal' => false,
		'nowait' => false,
		'arguments' => null,
		'ticket' => null,
		'declare' => true,
	];

	/**
	 * @var bool
	 */
	protected $exchangeDeclared = false;

	/**
	 * @var array
	 */
	protected $queueOptions = [
		'name' => '',
		'passive' => false,
		'durable' => true,
		'exclusive' => false,
		'autoDelete' => false,
		'nowait' => false,
		'arguments' => null,
		'ticket' => null,
		'routing_keys' => [],
	];

	/**
	 * @var bool
	 */
	protected $queueDeclared = false;



	/**
	 * @param Connection $conn
	 * @param string $consumerTag
	 */
	public function __construct(Connection $conn, $consumerTag = null)
	{
		$this->conn = $conn;
		$this->consumerTag = empty($consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $consumerTag;

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
	 * @param AMQPChannel $ch
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
	public function setExchangeOptions(array $options = [])
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
	 * @return array
	 */
	public function getExchangeOptions()
	{
		return $this->exchangeOptions;
	}



	/**
	 * @param  array $options
	 * @return void
	 */
	public function setQueueOptions(array $options = [])
	{
		$this->queueOptions = $options + $this->queueOptions;
	}



	/**
	 * @return array
	 */
	public function getQueueOptions()
	{
		return $this->queueOptions;
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
			$this->exchangeOptions['ticket']);

		$this->exchangeDeclared = true;
	}



	protected function queueDeclare()
	{
		if (empty($this->queueOptions['name'])) {
			return;
		}

		$this->doQueueDeclare($this->queueOptions['name'], $this->queueOptions);
		$this->queueDeclared = true;
	}



	protected function doQueueDeclare($name, array $options)
	{
		list($queueName, ,) = $this->getChannel()->queue_declare(
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
