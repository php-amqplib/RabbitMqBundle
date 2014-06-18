<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;



class MultipleConsumer extends Consumer
{

	protected $queues = array();



	public function getQueueConsumerTag($queue)
	{
		return sprintf('%s-%s', $this->getConsumerTag(), $queue);
	}



	public function setQueues(array $queues)
	{
		$this->queues = $queues;
	}



	protected function setupConsumer()
	{
		if ($this->autoSetupFabric) {
			$this->setupFabric();
		}

		foreach ($this->queues as $name => $options) {
			//PHP 5.3 Compliant
			$currentObject = $this;

			$this->getChannel()->basic_consume($name, $this->getQueueConsumerTag($name), false, false, false, false, function (AMQPMessage $msg) use ($currentObject, $name) {
				$this->processQueueMessage($name, $msg);
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

		$processFlag = call_user_func($this->queues[$queueName]['callback'], $msg);

		$this->handleProcessMessage($msg, $processFlag);
	}
}
