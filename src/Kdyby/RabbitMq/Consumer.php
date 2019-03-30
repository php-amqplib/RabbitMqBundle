<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @method onStart(\Kdyby\RabbitMq\Consumer $self)
 * @method onConsume(\Kdyby\RabbitMq\Consumer $self, \PhpAmqpLib\Message\AMQPMessage $msg)
 * @method onReject(\Kdyby\RabbitMq\Consumer $self, \PhpAmqpLib\Message\AMQPMessage $msg, $processFlag)
 * @method onAck(\Kdyby\RabbitMq\Consumer $self, \PhpAmqpLib\Message\AMQPMessage $msg)
 * @method onError(\Kdyby\RabbitMq\Consumer $self, \PhpAmqpLib\Exception\AMQPExceptionInterface $e)
 * @method onTimeout(\Kdyby\RabbitMq\Consumer $self)
 */
class Consumer extends \Kdyby\RabbitMq\BaseConsumer
{

	/**
	 * @var array
	 */
	public $onConsume = [];

	/**
	 * @var array
	 */
	public $onReject = [];

	/**
	 * @var array
	 */
	public $onAck = [];

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
	public $onTimeout = [];

	/**
	 * @var array
	 */
	public $onError = [];

	/**
	 * @var int $memoryLimit
	 */
	protected $memoryLimit;

	/**
	 * Set the memory limit
	 *
	 * @param int $memoryLimit
	 */
	public function setMemoryLimit(int $memoryLimit): void
	{
		$this->memoryLimit = $memoryLimit;
	}

	/**
	 * Get the memory limit
	 */
	public function getMemoryLimit(): ?int
	{
		return $this->memoryLimit;
	}

	public function consume(int $msgAmount): void
	{
		$this->target = $msgAmount;
		$this->setupConsumer();
		$this->onStart($this);

		$previousErrorHandler = \set_error_handler(static function ($severity, $message, $file, $line, $context) use (&$previousErrorHandler) {
			if (!\preg_match('~stream_select\\(\\)~i', $message)) {
				$args = \func_get_args();
				return \call_user_func_array($previousErrorHandler, $args);
			}

			throw new \PhpAmqpLib\Exception\AMQPRuntimeException($message . ' in ' . $file . ':' . $line, (int) $severity);
		});

		try {
			while (\count($this->getChannel()->callbacks)) {
				$this->maybeStopConsumer();

				try {
					$this->getChannel()->wait(NULL, FALSE, $this->getIdleTimeout());
				} catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
					$this->onTimeout($this);
					// nothing bad happened, right?
					// intentionally not throwing the exception
				}
			}

		} catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
			\restore_error_handler();

			// sending kill signal to the consumer causes the stream_select to return false
			// the reader doesn't like the false value, so it throws AMQPRuntimeException
			$this->maybeStopConsumer();
			if ( ! $this->forceStop) {
				$this->onError($this, $e);
				throw $e;
			}

		} catch (AMQPExceptionInterface $e) {
			\restore_error_handler();

			$this->onError($this, $e);
			throw $e;

		} catch (\Kdyby\RabbitMq\Exception\TerminateException $e) {
			$this->stopConsuming();
		}
	}

	/**
	 * Purge the queue
	 */
	public function purge(): void
	{
		$this->getChannel()->queue_purge($this->queueOptions['name'], TRUE);
	}

	public function processMessage(AMQPMessage $msg): void
	{
		$this->onConsume($this, $msg);
		try {
			$processFlag = \call_user_func($this->callback, $msg);
			$this->handleProcessMessage($msg, $processFlag);

		} catch (\Kdyby\RabbitMq\Exception\TerminateException $e) {
			$this->handleProcessMessage($msg, $e->getResponse());
			throw $e;

		} catch (\Throwable $e) {
			$this->onReject($this, $msg, IConsumer::MSG_REJECT_REQUEUE);
			throw $e;
		}
	}

	/**
	 * @param \PhpAmqpLib\Message\AMQPMessage $msg
	 * @param int|bool $processFlag
	 */
	protected function handleProcessMessage(AMQPMessage $msg, $processFlag): void
	{
		if ($processFlag === IConsumer::MSG_REJECT_REQUEUE || $processFlag === FALSE) {
			// Reject and requeue message to RabbitMQ
			$msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], TRUE);
			$this->onReject($this, $msg, $processFlag);

		} elseif ($processFlag === IConsumer::MSG_SINGLE_NACK_REQUEUE) {
			// NACK and requeue message to RabbitMQ
			$msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], FALSE, TRUE);
			$this->onReject($this, $msg, $processFlag);

		} else {
			if ($processFlag === IConsumer::MSG_REJECT) {
				// Reject and drop
				$msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], FALSE);
				$this->onReject($this, $msg, $processFlag);

			} else {
				// Remove message from queue only if callback return not false
				$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
				$this->onAck($this, $msg);
			}
		}

		$this->consumed++;
		$this->maybeStopConsumer();

		if ($this->isRamAlmostOverloaded()) {
			$this->stopConsuming();
		}
	}

	/**
	 * Checks if memory in use is greater or equal than memory allowed for this process
	 *
	 * @return bool
	 */
	protected function isRamAlmostOverloaded(): bool
	{
		if ($this->getMemoryLimit() === NULL) {
			return FALSE;
		}

		return \memory_get_usage(TRUE) >= ($this->getMemoryLimit() * 1024 * 1024);
	}

}
