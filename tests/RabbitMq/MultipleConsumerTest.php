<?php

declare(strict_types = 1);

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\MultipleConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class MultipleConsumerTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * Check if the message is requeued or not correctly.
	 *
	 * @dataProvider processMessageProvider
	 */
	public function testProcessMessage($processFlag, $expectedMethod, $expectedRequeue = NULL): void
	{
		$amqpConnection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
			->disableOriginalConstructor()
			->getMock();

		$amqpChannel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
			->disableOriginalConstructor()
			->getMock();

		$consumer = new MultipleConsumer($amqpConnection, $amqpChannel);
		$callback = static function ($msg) use ($processFlag) {
			return $processFlag;
		};

		$consumer->setQueues(['test-1' => ['callback' => $callback], 'test-2' => ['callback' => $callback]]);

		// Create a default message
		$amqpMessage = new AMQPMessage('foo body');
		$amqpMessage->delivery_info['channel'] = $amqpChannel;
		$amqpMessage->delivery_info['delivery_tag'] = 0;
		$amqpChannel->expects($this->any())
			->method('basic_reject')
			->will($this->returnCallback(static function ($delivery_tag, $requeue) use ($expectedMethod, $expectedRequeue): void {
				\PHPUnit_Framework_Assert::assertSame($expectedMethod, 'basic_reject'); // Check if this function should be called.
				\PHPUnit_Framework_Assert::assertSame($requeue, $expectedRequeue); // Check if the message should be requeued.
			}));

		$amqpChannel->expects($this->any())
			->method('basic_ack')
			->will($this->returnCallback(static function ($delivery_tag) use ($expectedMethod): void {
				\PHPUnit_Framework_Assert::assertSame($expectedMethod, 'basic_ack'); // Check if this function should be called.
			}));

		$consumer->processQueueMessage('test-1', $amqpMessage);
		$consumer->processQueueMessage('test-2', $amqpMessage);
	}

	public function processMessageProvider()
	{
		return [
			[NULL, 'basic_ack'], // Remove message from queue only if callback return not false
			[TRUE, 'basic_ack'], // Remove message from queue only if callback return not false
			[FALSE, 'basic_reject', TRUE], // Reject and requeue message to RabbitMQ
			[ConsumerInterface::MSG_ACK, 'basic_ack'], // Remove message from queue only if callback return not false
			[ConsumerInterface::MSG_REJECT_REQUEUE, 'basic_reject', TRUE], // Reject and requeue message to RabbitMQ
			[ConsumerInterface::MSG_REJECT, 'basic_reject', FALSE], // Reject and drop
		];
	}

}
