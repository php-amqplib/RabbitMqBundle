<?php

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
    public function testProcessMessage($processFlag, $expectedMethod, $expectedRequeue = null)
    {
        $amqpConnection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpChannel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $consumer = new MultipleConsumer($amqpConnection, $amqpChannel);
        $callback = function($msg) use (&$lastQueue, $processFlag) {
            return $processFlag;
        };

        $consumer->setQueues(array('test-1' => array('callback' => $callback), 'test-2'  => array('callback' => $callback)));

        // Create a default message
        $amqpMessage = new AMQPMessage('foo body');
        $amqpMessage->delivery_info['channel'] = $amqpChannel;
        $amqpMessage->delivery_info['delivery_tag'] = 0;
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

        $consumer->processQueueMessage('test-1', $amqpMessage);
        $consumer->processQueueMessage('test-2', $amqpMessage);
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
        );
    }
} 