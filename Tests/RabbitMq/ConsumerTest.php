<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if the message is requeued or not correctly.
     *
     * @dataProvider processMessageProvider
     */
    public function testProcessMessage($processFlag, $expectedMethod, $expectedRequeue = null,
        $useInteractive = false, $stopConsume = false)
    {
        $amqpConnection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpChannel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        if (false === $useInteractive) {
        $callback = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        } else {
            $callback = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\InteractiveConsumerInterface')
                ->disableOriginalConstructor()
                ->getMock();

            $callback
                ->expects($this->once())
                ->method('mustStopConsumer')
                ->will($this->returnValue($stopConsume));

            if (true === $stopConsume) {
                $amqpChannel
                    ->expects($this->once())
                    ->method('basic_cancel');
            }
        }

        $consumer = new Consumer($amqpConnection, $amqpChannel);

        // Create a default message
        $amqpMessage = new AMQPMessage('foo body');
        $amqpMessage->delivery_info['channel'] = $amqpChannel;
        $amqpMessage->delivery_info['delivery_tag'] = 0;

        // Configure callback call
        $callback
            ->expects($this->once())
            ->method('execute')
            ->with($amqpMessage)
            ->will($this->returnValue($processFlag));

        $consumer->setCallback($callback);

        $amqpChannel->expects($this->any())
            ->method('basic_reject')
            ->will($this->returnCallback(function($delivery_tag, $requeue) use ($expectedMethod, $expectedRequeue) {
                \PHPUnit_Framework_Assert::assertSame($expectedMethod, 'basic_reject'); // Check if this function should be called.
                \PHPUnit_Framework_Assert::assertSame($requeue, $expectedRequeue); // Check if the message should be requeued.
            }));

        $amqpChannel->expects($this->any())
            ->method('basic_ack')
            ->will($this->returnCallback(function($delivery_tag) use ($expectedMethod) {
                \PHPUnit_Framework_Assert::assertSame($expectedMethod, 'basic_ack'); // Check if this function should be called.
            }));

        $consumer->processMessage($amqpMessage);
    }

    public function processMessageProvider()
    {
        return array(
            array(null, 'basic_ack'), // Remove message from queue only if callback return not false
            array(true, 'basic_ack'), // Remove message from queue only if callback return not false
            array(false, 'basic_reject', true), // Reject and requeue message to RabbitMQ
            array(ConsumerInterface::MSG_ACK, 'basic_ack'), // Remove message from queue only if callback return not false
            array(ConsumerInterface::MSG_REJECT_REQUEUE, 'basic_reject', true), // Reject and requeue message to RabbitMQ
            array(ConsumerInterface::MSG_REJECT, 'basic_reject', false), // Reject and drop
            array(null, 'basic_ack', null, true, false), // Remove message from queue only if callback return not false
            array(null, 'basic_ack', null, true, true), // Remove message from queue only if callback return not false
        );
    }
}
