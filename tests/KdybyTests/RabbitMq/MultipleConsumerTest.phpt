<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\RabbitMq\Extension.
 *
 * @testCase KdybyTests\RabbitMq\MultipleConsumerTest
 */

namespace KdybyTests\RabbitMq;

use Kdyby\RabbitMq\IConsumer;
use Kdyby\RabbitMq\MultipleConsumer;
use PhpAmqpLib\Message\AMQPMessage;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class MultipleConsumerTest extends \KdybyTests\RabbitMq\TestCase
{

	/**
	 * Check if the message is requeued or not correctly.
	 *
	 * @dataProvider processMessageProvider
	 * @param mixed $processFlag
	 * @param string $expectedMethod
	 * @param bool|null $expectedRequeue
	 */
	public function testProcessMessage($processFlag, string $expectedMethod, ?bool $expectedRequeue = NULL): void
	{
		Assert::noError(function () use (
			$processFlag,
			$expectedMethod,
			$expectedRequeue
		): void {
			/** @var \Kdyby\RabbitMq\Connection|\Mockery\Mock $amqpConnection */
			$amqpConnection = $this->getMockery(\Kdyby\RabbitMq\Connection::class, ['127.0.0.1', 5672, 'guest', 'guest'])
				->makePartial();

			/** @var \Kdyby\RabbitMq\Channel|\Mockery\Mock $amqpChannel */
			$amqpChannel = $this->getMockery(\Kdyby\RabbitMq\Channel::class, [$amqpConnection])
				->makePartial();
			$consumer = new MultipleConsumer($amqpConnection);
			$consumer->setChannel($amqpChannel);

			$callback = static function ($msg) use ($processFlag) {
				return $processFlag;
			};
			$consumer->setQueues(['test-1' => ['callback' => $callback], 'test-2' => ['callback' => $callback]]);

			// Create a default message
			$amqpMessage = new AMQPMessage('foo body');
			$amqpMessage->delivery_info['channel'] = $amqpChannel;
			$amqpMessage->delivery_info['delivery_tag'] = 0;

			$amqpChannel->shouldReceive('basic_reject')
				->andReturnUsing(
					static function (
						$deliveryTag,
						$requeue
					) use (
						$expectedMethod,
						$expectedRequeue
					): void {
						Assert::same($expectedMethod, 'basic_reject'); // Check if this function should be called.
						Assert::same($requeue, $expectedRequeue); // Check if the message should be requeued.
					}
				);

			$amqpChannel->shouldReceive('basic_ack')
				->andReturnUsing(
					static function ($deliveryTag) use ($expectedMethod): void {
						Assert::same($expectedMethod, 'basic_ack'); // Check if this function should be called.
					}
				);

			$consumer->processQueueMessage('test-1', $amqpMessage);
			$consumer->processQueueMessage('test-2', $amqpMessage);
		});
	}

	/**
	 * @return array<mixed>
	 */
	public function processMessageProvider(): array
	{
		return [
			[NULL, 'basic_ack'], // Remove message from queue only if callback return not false
			[TRUE, 'basic_ack'], // Remove message from queue only if callback return not false
			[FALSE, 'basic_reject', TRUE], // Reject and requeue message to RabbitMQ
			[IConsumer::MSG_ACK, 'basic_ack'], // Remove message from queue only if callback return not false
			[IConsumer::MSG_REJECT_REQUEUE, 'basic_reject', TRUE], // Reject and requeue message to RabbitMQ
			[IConsumer::MSG_REJECT, 'basic_reject', FALSE], // Reject and drop
		];
	}

}

(new MultipleConsumerTest())->run();
