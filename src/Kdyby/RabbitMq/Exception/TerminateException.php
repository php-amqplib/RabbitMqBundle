<?php declare(strict_types = 1);

namespace Kdyby\RabbitMq\Exception;


class TerminateException extends \RuntimeException implements \Kdyby\RabbitMq\Exception\Exception
{

	private $response = \Kdyby\RabbitMq\IConsumer::MSG_REJECT_REQUEUE;


	/**
	 * @param int $response
	 * @return TerminateException
	 */
	public static function withResponse($response)
	{
		$e = new self();
		$e->response = $response;

		return $e;
	}


	/**
	 * @return int
	 */
	public function getResponse()
	{
		return $this->response;
	}

}