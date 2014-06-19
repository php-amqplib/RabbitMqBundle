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
