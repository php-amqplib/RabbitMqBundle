<?php

namespace Kdyby\RabbitMq\Command;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class ConsumerCommand extends BaseConsumerCommand
{

	protected function configure()
	{
		parent::configure();

		$this->setName('rabbitmq:consumer');
		$this->setDescription('Starts a configured consumer');
	}

}
