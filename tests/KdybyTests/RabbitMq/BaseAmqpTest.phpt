<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\RabbitMq\Extension.
 *
 * @testCase KdybyTests\RabbitMq\BaseAmqpTest
 */

namespace KdybyTests\RabbitMq;

use Kdyby\RabbitMq\Connection;
use Kdyby\RabbitMq\Consumer;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


class BaseAmqpTest extends \KdybyTests\RabbitMq\TestCase
{

	public function testLazyConnection(): void
	{
		$lazyConnection = new Connection('localhost', 123, 'lazy_user', 'lazy_password');
		$consumer = new Consumer($lazyConnection);

		Assert::exception(static function () use ($consumer): void {
			$consumer->getChannel();
		}, \PhpAmqpLib\Exception\AMQPIOException::class, 'stream_socket_client(): Unable to connect to tcp://localhost:123 (Connection refused)');
	}

}

(new BaseAmqpTest())->run();
