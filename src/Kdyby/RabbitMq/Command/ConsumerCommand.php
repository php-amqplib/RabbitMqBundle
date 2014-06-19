<?php

namespace Kdyby\RabbitMq\Command;

class ConsumerCommand extends BaseConsumerCommand
{

	protected function configure()
	{
		parent::configure();

		$this->setName('rabbitmq:consumer');
		$this->setDescription('Starts a configured consumer');
	}

}
