<?php

/**
 * Test: Kdyby\RabbitMq\Extension.
 *
 * @testCase KdybyTests\RabbitMq\BaseAmqpTest
 * @package Kdyby\RabbitMq
 */

namespace KdybyTests\RabbitMq;

use Kdyby;
use Kdyby\RabbitMq\Connection;
use Kdyby\RabbitMq\Consumer;
use KdybyTests;
use Nette;
use Tester;
use Tester\Assert;



require_once __DIR__ . '/TestCase.php';

class BaseAmqpTest extends TestCase
{

	public function testLazyConnection()
	{
		$lazyConnection = new Connection('localhost', 123, 'lazy_user', 'lazy_password');
		$consumer = new Consumer($lazyConnection);

		Assert::exception(function () use ($consumer) {
			$consumer->getChannel();
		}, 'PhpAmqpLib\Exception\AMQPRuntimeException', 'Error Connecting to server(111): Connection refused');
	}

}

\run(new BaseAmqpTest());
