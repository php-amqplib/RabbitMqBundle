<?php

namespace Kdyby\RabbitMq;


/**
 * Common interface for caching github exceptions
 *
 * @author Filip Procházka <filip@prochazka.com>
 */
interface Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.com>
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 */
class QueueNotFoundException extends \RuntimeException implements Exception
{

}



class TerminateException extends \RuntimeException implements Exception
{

	private $response = IConsumer::MSG_REJECT_REQUEUE;



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
