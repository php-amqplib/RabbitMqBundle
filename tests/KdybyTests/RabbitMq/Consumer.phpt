<?php

/**
 * Test: Kdyby\RabbitMq\Extension.
 *
 * @testCase KdybyTests\RabbitMq\ExtensionTest
 * @package Kdyby\RabbitMq
 */

namespace KdybyTests\RabbitMq;

use Kdyby;
use Kdyby\RabbitMq\Consumer;
use Kdyby\RabbitMq\IConsumer;
use KdybyTests;
use Mockery\Mock;
use Nette;
use PhpAmqpLib\Message\AMQPMessage;
use Tester;
use Tester\Assert;



require_once __DIR__ . '/TestCase.php';

class ConsumerTest extends TestCase
{
    /**
     * Check if the message is requeued or not correctly.
     *
     * @dataProvider processMessageProvider
     */
    public function testProcessMessage($processFlag, $expectedMethod, $expectedRequeue = null)
    {
		/** @var Kdyby\RabbitMq\Connection|Mock $amqpConnection */
		$amqpConnection = $this->getMockery('Kdyby\RabbitMq\Connection', array('127.0.0.1', 5672, 'guest', 'guest'))
			->makePartial();

		/** @var Kdyby\RabbitMq\Channel|Mock $amqpChannel */
        $amqpChannel = $this->getMockery('Kdyby\RabbitMq\Channel', array($amqpConnection))
			->makePartial();

        $consumer = new Consumer($amqpConnection);
		$consumer->setChannel($amqpChannel);

        $callbackFunction = function() use ($processFlag) { return $processFlag; }; // Create a callback function with a return value set by the data provider.
        $consumer->setCallback($callbackFunction);

        // Create a default message
        $amqpMessage = new AMQPMessage('foo body');
        $amqpMessage->delivery_info['channel'] = $amqpChannel;
        $amqpMessage->delivery_info['delivery_tag'] = 0;

        $amqpChannel->shouldReceive('basic_reject')
            ->andReturnUsing(function($delivery_tag, $requeue) use ($expectedMethod, $expectedRequeue) {
                Assert::same($expectedMethod, 'basic_reject'); // Check if this function should be called.
                Assert::same($requeue, $expectedRequeue); // Check if the message should be requeued.
            });

        $amqpChannel->shouldReceive('basic_ack')
            ->andReturnUsing(function($delivery_tag) use ($expectedMethod) {
                Assert::same($expectedMethod, 'basic_ack'); // Check if this function should be called.
            });

        $consumer->processMessage($amqpMessage);
    }



    public function processMessageProvider()
    {
        return array(
            array(null, 'basic_ack'), // Remove message from queue only if callback return not false
            array(true, 'basic_ack'), // Remove message from queue only if callback return not false
            array(false, 'basic_reject', true), // Reject and requeue message to RabbitMQ
            array(IConsumer::MSG_ACK, 'basic_ack'), // Remove message from queue only if callback return not false
            array(IConsumer::MSG_REJECT_REQUEUE, 'basic_reject', true), // Reject and requeue message to RabbitMQ
            array(IConsumer::MSG_REJECT, 'basic_reject', false), // Reject and drop
        );
    }

}

\run(new ConsumerTest());
