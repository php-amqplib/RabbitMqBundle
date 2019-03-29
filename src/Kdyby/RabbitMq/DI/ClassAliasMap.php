<?php

declare(strict_types = 1);

return [
	\Kdyby\RabbitMq\Exception::class                => \Kdyby\RabbitMq\Exception\Exception::class,
	\Kdyby\RabbitMq\InvalidArgumentException::class => \Kdyby\RabbitMq\Exception\InvalidArgumentException::class,
	\Kdyby\RabbitMq\QueueNotFoundException::class   => \Kdyby\RabbitMq\Exception\QueueNotFoundException::class,
	\Kdyby\RabbitMq\TerminateException::class       => \Kdyby\RabbitMq\Exception\TerminateException::class,
];
