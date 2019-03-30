<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

class AnonymousConsumer extends \Kdyby\RabbitMq\Consumer
{

	public function __construct(Connection $conn)
	{
		parent::__construct($conn);

		$this->setQueueOptions([
			'name' => '',
			'passive' => FALSE,
			'durable' => FALSE,
			'exclusive' => TRUE,
			'autoDelete' => TRUE,
			'nowait' => FALSE,
			'arguments' => NULL,
			'ticket' => NULL,
		]);
	}

}
