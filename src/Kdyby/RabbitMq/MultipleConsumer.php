<?php

namespace Kdyby\RabbitMq;

use Nette\Utils\Callback;
use PhpAmqpLib\Message\AMQPMessage;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class MultipleConsumer extends Consumer
{

	/**
	 * @var array
	 */
	public $onConsume = [];

	/**
	 * @var array[]|callable[][]
	 */
	protected $queues = [];



	public function getQueueConsumerTag($queue)
	{
		return sprintf('%s-%s', $this->getConsumerTag(), $queue);
	}



	public function setQueues(array $queues)
	{
		$this->queues = [];
		foreach ($queues as $name => $queue) {
			if (!isset($queue['callback'])) {
				throw new InvalidArgumentException("The queue '$name' is missing a callback.");
			}

			Callback::check($queue['callback']);
			$this->queues[$name] = $queue;
		}
	}



	/**
	 * @return \array[]|\callable[][]
	 */
	public function getQueues()
	{
		return $this->queues;
	}



	protected function setupConsumer()
	{
		if ($this->autoSetupFabric) {
			$this->setupFabric();
		}

		if ( ! $this->qosDeclared) {
			$this->qosDeclare();
		}

		foreach ($this->queues as $name => $options) {
			$self = $this;
			$this->getChannel()->basic_consume($name, $this->getQueueConsumerTag($name), false, false, false, false, function (AMQPMessage $msg) use ($self, $name) {
				$self->processQueueMessage($name, $msg);
			});
		}
	}



	protected function queueDeclare()
	{
		foreach ($this->queues as $name => $options) {
			$this->doQueueDeclare($name, $options);
		}

		$this->queueDeclared = true;
	}



	public function processQueueMessage($queueName, AMQPMessage $msg)
	{
		if (!isset($this->queues[$queueName])) {
			throw new QueueNotFoundException();
		}

		$this->onConsume($this, $msg);
		try {
			$processFlag = call_user_func($this->queues[$queueName]['callback'], $msg);
			$this->handleProcessMessage($msg, $processFlag);

		} catch (TerminateException $e) {
			$this->handleProcessMessage($msg, $e->getResponse());
			throw $e;

		} catch (\Exception $e) {
			$this->onReject($this, $msg, IConsumer::MSG_REJECT_REQUEUE);
			throw $e;
		}
	}
}
