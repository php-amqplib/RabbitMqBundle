<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

use Nette\Utils\Callback;
use PhpAmqpLib\Message\AMQPMessage;

class MultipleConsumer extends \Kdyby\RabbitMq\Consumer
{

	/**
	 * @var array[]|callable[][]
	 */
	protected $queues = [];

	public function getQueueConsumerTag(string $queue): string
	{
		return \sprintf('%s-%s', $this->getConsumerTag(), $queue);
	}

	/**
	 * @param array<string, callable> $queues
	 */
	public function setQueues(array $queues): void
	{
		$this->queues = [];
		foreach ($queues as $name => $queue) {
			if (!isset($queue['callback'])) {
				throw new \Kdyby\RabbitMq\Exception\InvalidArgumentException(
					\sprintf("The queue '%s' is missing a callback.", $name)
				);
			}

			Callback::check($queue['callback']);
			$this->queues[$name] = $queue;
		}
	}

	/**
	 * @return array<mixed>
	 */
	public function getQueues(): array
	{
		return $this->queues;
	}

	protected function setupConsumer(): void
	{
		if ($this->autoSetupFabric) {
			$this->setupFabric();
		}

		if ( ! $this->qosDeclared) {
			$this->qosDeclare();
		}

		foreach (\array_keys($this->queues) as $name) {
			$self = $this;
			$this->getChannel()->basic_consume($name, $this->getQueueConsumerTag($name), FALSE, FALSE, FALSE, FALSE, static function (AMQPMessage $msg) use ($self, $name): void {
				$self->processQueueMessage($name, $msg);
			});
		}
	}

	protected function queueDeclare(): void
	{
		foreach ($this->queues as $name => $options) {
			$this->doQueueDeclare($name, $options);
		}

		$this->queueDeclared = TRUE;
	}

	public function processQueueMessage(string $queueName, AMQPMessage $msg): void
	{
		if (!isset($this->queues[$queueName])) {
			throw new \Kdyby\RabbitMq\Exception\QueueNotFoundException();
		}

		$this->onConsume($this, $msg);
		try {
			$processFlag = \call_user_func($this->queues[$queueName]['callback'], $msg);
			$this->handleProcessMessage($msg, $processFlag);

		} catch (\Kdyby\RabbitMq\Exception\TerminateException $e) {
			$this->handleProcessMessage($msg, $e->getResponse());
			throw $e;

		} catch (\Throwable $e) {
			$this->onReject($this, $msg, IConsumer::MSG_REJECT_REQUEUE);
			throw $e;
		}
	}

}
