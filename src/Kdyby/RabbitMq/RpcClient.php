<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class RpcClient extends AmqpMember
{

	/**
	 * @var int
	 */
	protected $requests = 0;

	/**
	 * @var array
	 */
	protected $replies = [];

	/**
	 * @var string
	 */
	protected $queueName;

	/**
	 * @var bool
	 */
	protected $expectSerializedResponse;

	/**
	 * @var int
	 */
	protected $timeout = 0;



	public function initClient($expectSerializedResponse = true)
	{
		list($this->queueName,,) = $this->getChannel()->queue_declare(
			"",
			$passive = false,
			$durable = false,
			$exclusive = true,
			$autoDelete = true
		);

		$this->expectSerializedResponse = $expectSerializedResponse;
	}



	public function addRequest($msgBody, $server, $requestId = null, $routingKey = '', $expiration = 0)
	{
		if (empty($requestId)) {
			throw new \InvalidArgumentException('You must provide a $requestId');
		}

		$msg = new AMQPMessage($msgBody, [
			'content_type' => 'text/plain',
			'reply_to' => $this->queueName,
			'delivery_mode' => 1, // non durable
			'expiration' => $expiration * 1000,
			'correlation_id' => $requestId
		]);

		$this->getChannel()->basic_publish($msg, $server, $routingKey);

		$this->requests++;

		if ($expiration > $this->timeout) {
			$this->timeout = $expiration;
		}
	}



	public function getReplies()
	{
		$this->replies = [];
		$this->getChannel()->basic_consume($this->queueName, '', false, true, false, false, [$this, 'processMessage']);

		while (count($this->replies) < $this->requests) {
			$this->getChannel()->wait(null, false, $this->timeout);
		}

		$this->getChannel()->basic_cancel($this->queueName);
		$this->requests = 0;
		$this->timeout = 0;

		return $this->replies;
	}



	public function processMessage(AMQPMessage $msg)
	{
		$messageBody = $msg->body;
		if ($this->expectSerializedResponse) {
			$messageBody = unserialize($messageBody);
		}

		$this->replies[$msg->get('correlation_id')] = $messageBody;
	}

}
