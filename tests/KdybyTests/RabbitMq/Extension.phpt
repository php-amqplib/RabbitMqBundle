<?php

/**
 * Test: Kdyby\RabbitMq\Extension.
 *
 * @testCase KdybyTests\RabbitMq\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\RabbitMq
 */

namespace KdybyTests\RabbitMq;

use Kdyby;
use KdybyTests;
use Nette;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/TestCase.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends TestCase
{

	/**
	 * @return \SystemContainer|\Nette\DI\Container
	 */
	protected function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		Kdyby\RabbitMq\DI\RabbitMqExtension::register($config);
		$configVersion = !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22';
		$config->addConfig(__DIR__ . '/../nette-reset.' . $configVersion . '.neon');
		$config->addConfig(__DIR__ . '/files/default.neon');

		return $config->createContainer();
	}



	public function testFunctional()
	{
		$dic = $this->createContainer();

		// foo was defined first in config
		Assert::true($dic->getByType('Kdyby\RabbitMq\Connection') instanceof AMQPStreamConnection);
		Assert::same($dic->getByType('Kdyby\RabbitMq\Connection'), $dic->getService('rabbitmq.foo_connection.connection'));

		// only the first defined connection is autowired
		Assert::true($dic->getService('rabbitmq.default.connection') instanceof AMQPStreamConnection);
		Assert::notSame($dic->getByType('Kdyby\RabbitMq\Connection'), $dic->getService('rabbitmq.default.connection'));

		Assert::true($dic->getService('rabbitmq.producer.foo_producer') instanceof Kdyby\RabbitMq\Producer);
		Assert::true($dic->getService('rabbitmq.producer.default_producer') instanceof Kdyby\RabbitMq\Producer);

		Assert::true($dic->getService('rabbitmq.consumer.foo_consumer') instanceof Kdyby\RabbitMq\Consumer);
		Assert::true($dic->getService('rabbitmq.consumer.default_consumer') instanceof Kdyby\RabbitMq\Consumer);
		Assert::true($dic->getService('rabbitmq.consumer.qos_test_consumer') instanceof Kdyby\RabbitMq\Consumer);
		Assert::true($dic->getService('rabbitmq.consumer.multi_test_consumer') instanceof Kdyby\RabbitMq\MultipleConsumer);
		Assert::true($dic->getService('rabbitmq.consumer.foo_anon_consumer') instanceof Kdyby\RabbitMq\AnonymousConsumer);
		Assert::true($dic->getService('rabbitmq.consumer.default_anon_consumer') instanceof Kdyby\RabbitMq\AnonymousConsumer);

		Assert::true($dic->getService('rabbitmq.rpcClient.foo_client') instanceof Kdyby\RabbitMq\RpcClient);
		Assert::true($dic->getService('rabbitmq.rpcClient.default_client') instanceof Kdyby\RabbitMq\RpcClient);

		Assert::true($dic->getService('rabbitmq.rpcServer.foo_server') instanceof Kdyby\RabbitMq\RpcServer);
		Assert::true($dic->getService('rabbitmq.rpcServer.default_server') instanceof Kdyby\RabbitMq\RpcServer);
	}

}

\run(new ExtensionTest());
