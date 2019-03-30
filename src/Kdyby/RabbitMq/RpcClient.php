<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

class RpcClient extends \Kdyby\RabbitMq\AmqpMember
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

	public function initClient(bool $expectSerializedResponse = TRUE): void
	{
		[$this->queueName] = $this->getChannel()->queue_declare(
			'',
			$passive = FALSE,
			$durable = FALSE,
			$exclusive = TRUE,
			$autoDelete = TRUE
		);

		$this->expectSerializedResponse = $expectSerializedResponse;
	}

	/**
	 * @param string $msgBody
	 * @param string $server
	 * @param mixed $requestId
	 * @param string $routingKey
	 * @param int $expiration
	 */
	public function addRequest(string $msgBody, string $server, $requestId = NULL, string $routingKey = '', int $expiration = 0): void
	{
		if (empty($requestId)) {
			throw new \InvalidArgumentException('You must provide a $requestId');
		}

		$msg = new AMQPMessage($msgBody, [
			'content_type' => 'text/plain',
			'reply_to' => $this->queueName,
			'delivery_mode' => 1, // non durable
			'expiration' => $expiration * 1000,
			'correlation_id' => $requestId,
		]);

		$this->getChannel()->basic_publish($msg, $server, $routingKey);

		$this->requests++;

		if ($expiration > $this->timeout) {
			$this->timeout = $expiration;
		}
	}

	/**
	 * @return array<mixed>
	 */
	public function getReplies(): array
	{
		$this->replies = [];
		$this->getChannel()->basic_consume($this->queueName, '', FALSE, TRUE, FALSE, FALSE, [$this, 'processMessage']);

		while (\count($this->replies) < $this->requests) {
			$this->getChannel()->wait(NULL, FALSE, $this->timeout);
		}

		$this->getChannel()->basic_cancel($this->queueName);
		$this->requests = 0;
		$this->timeout = 0;

		return $this->replies;
	}

	public function processMessage(AMQPMessage $msg): void
	{
		$messageBody = $msg->body;
		if ($this->expectSerializedResponse) {
			$messageBody = \unserialize($messageBody);
		}

		$this->replies[$msg->get('correlation_id')] = $messageBody;
	}

}
