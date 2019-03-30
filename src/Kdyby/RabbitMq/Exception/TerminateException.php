<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Exception;

class TerminateException extends \RuntimeException implements \Kdyby\RabbitMq\Exception\Exception
{

	/**
	 * @var int
	 */
	private $response = \Kdyby\RabbitMq\IConsumer::MSG_REJECT_REQUEUE;

	public static function withResponse(int $response): \Kdyby\RabbitMq\Exception\TerminateException
	{
		$e = new self();
		$e->response = $response;

		return $e;
	}

	public function getResponse(): int
	{
		return $this->response;
	}

}
