<?php

namespace Kdyby\RabbitMq\Command;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class MultipleConsumerCommand extends BaseConsumerCommand
{

	protected function configure()
	{
		parent::configure();

		$this->setName('rabbitmq:multiple-consumer');
	}



	protected function getConsumerService()
	{
		return 'old_sound_rabbit_mq.%s_multiple';
	}
}
