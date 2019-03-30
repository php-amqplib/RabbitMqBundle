<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @method onStart(\Kdyby\RabbitMq\RpcServer $self)
 * @method onConsume(\Kdyby\RabbitMq\RpcServer $self, \PhpAmqpLib\Message\AMQPMessage $msg)
 * @method onReply(\Kdyby\RabbitMq\RpcServer $self, $result)
 * @method onError(\Kdyby\RabbitMq\RpcServer $self, \PhpAmqpLib\Exception\AMQPExceptionInterface $e)
 */
class RpcServer extends \Kdyby\RabbitMq\BaseConsumer
{

	/**
	 * @var array
	 */
	public $onConsume = [];

	/**
	 * @var array
	 */
	public $onReply = [];

	/**
	 * @var array
	 */
	public $onStart = [];

	/**
	 * @var array
	 */
	public $onStop = [];

	/**
	 * @var array
	 */
	public $onError = [];

	public function initServer(string $name): void
	{
		$this->setExchangeOptions(['name' => $name, 'type' => 'direct']);
		$this->setQueueOptions(['name' => $name . '-queue']);
	}

	public function start(int $msgAmount = 0): void
	{
		$this->target = $msgAmount;
		$this->setupConsumer();
		$this->onStart($this);

		try {
			while (\count($this->getChannel()->callbacks)) {
				$this->maybeStopConsumer();

				try {
					$this->getChannel()->wait(NULL, FALSE, $this->getIdleTimeout());
				} catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
					// nothing bad happened, right?
				}
			}

		} catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
			// sending kill signal to the consumer causes the stream_select to return false
			// the reader doesn't like the false value, so it throws AMQPRuntimeException
			$this->maybeStopConsumer();
			if ( ! $this->forceStop) {
				$this->onError($this, $e);
				throw $e;
			}

		} catch (AMQPExceptionInterface $e) {
			$this->onError($this, $e);
			throw $e;
		}
	}

	public function processMessage(AMQPMessage $msg): void
	{
		try {
			$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
			$this->onConsume($this, $msg);

			$result = \call_user_func($this->callback, $msg);
			$this->onReply($this, $result);
			$this->sendReply(\serialize($result), $msg->get('reply_to'), $msg->get('correlation_id'));

			$this->consumed++;
			$this->maybeStopConsumer();

		} catch (\Throwable $e) {
			$this->sendReply('error: ' . $e->getMessage(), $msg->get('reply_to'), $msg->get('correlation_id'));
		}
	}

	/**
	 * @param string $result
	 * @param string $client
	 * @param mixed $correlationId
	 */
	protected function sendReply(string $result, string $client, $correlationId): void
	{
		$this->getChannel()->basic_publish(
			new AMQPMessage($result, [
				'content_type' => 'text/plain',
				'correlation_id' => $correlationId,
			]),
			$exchange = '',
			$client
		);
	}

}
