<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Connection\AMQPConnection;



class AnonymousConsumer extends Consumer
{

	public function __construct(AMQPConnection $conn)
	{
		parent::__construct($conn);

		$this->setQueueOptions(array(
			'name' => '',
			'passive' => false,
			'durable' => false,
			'exclusive' => true,
			'autoDelete' => true,
			'nowait' => false,
			'arguments' => null,
			'ticket' => null
		));
	}

}
