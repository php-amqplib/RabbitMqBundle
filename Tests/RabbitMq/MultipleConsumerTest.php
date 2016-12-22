<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\Provider\QueuesProviderInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\MultipleConsumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MultipleConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Multiple consumer
     *
     * @var MultipleConsumer
     */
    private $multipleConsumer;

    /**
     * AMQP channel
     *
     * @var \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    private $amqpChannel;

    /**
     * AMQP connection
     *
     * @var \PHPUnit_Framework_MockObject_MockObject|AMQPConnection
     */
    private $amqpConnection;

    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        $this->amqpConnection = $this->prepareAMQPConnection();
        $this->amqpChannel = $this->prepareAMQPChannel();
        $this->multipleConsumer = new MultipleConsumer($this->amqpConnection, $this->amqpChannel);
    }

    /**
     * Check if the message is requeued or not correctly.
     *
     * @dataProvider processMessageProvider
     */
    public function testProcessMessage($processFlag, $expectedMethod, $expectedRequeue = null)
    {
        $callback = $this->prepareCallback($processFlag);

        $this->multipleConsumer->setQueues(
            array(
                'test-1' => array('callback' => $callback),
                'test-2' => array('callback' => $callback)
            )
        );

        $this->prepareAMQPChannelExpectations($expectedMethod, $expectedRequeue);

        // Create a default message
        $amqpMessage = new AMQPMessage('foo body');
        $amqpMessage->delivery_info['channel'] = $this->amqpChannel;
        $amqpMessage->delivery_info['delivery_tag'] = 0;

        $this->multipleConsumer->processQueueMessage('test-1', $amqpMessage);
        $this->multipleConsumer->processQueueMessage('test-2', $amqpMessage);
    }

    /**
     * Check queues provider works well
     *
     * @dataProvider processMessageProvider
     */
    public function testQueuesProvider($processFlag, $expectedMethod, $expectedRequeue = null)
    {
        $callback = $this->prepareCallback($processFlag);

        $queuesProvider = $this->prepareQueuesProvider();
        $queuesProvider->expects($this->once())
            ->method('getQueues')
            ->will($this->returnValue(
                array(
                    'test-1' => array('callback' => $callback),
                    'test-2' => array('callback' => $callback)
                )
            ));

        $this->multipleConsumer->setQueuesProvider($queuesProvider);

        /**
         * We don't test consume method, which merges queues by calling $this->setupConsumer();
         * So we need to invoke it manually
         */
        $reflectionClass = new \ReflectionClass(get_class($this->multipleConsumer));
        $reflectionMethod = $reflectionClass->getMethod('mergeQueues');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->multipleConsumer);

        $this->prepareAMQPChannelExpectations($expectedMethod, $expectedRequeue);

        // Create a default message
        $amqpMessage = new AMQPMessage('foo body');
        $amqpMessage->delivery_info['channel'] = $this->amqpChannel;
        $amqpMessage->delivery_info['delivery_tag'] = 0;

        $this->multipleConsumer->processQueueMessage('test-1', $amqpMessage);
        $this->multipleConsumer->processQueueMessage('test-2', $amqpMessage);
    }
    
    public function testQueuesPrivider()
    {
        $amqpConnection = $this->prepareAMQPConnection();
        $amqpChannel = $this->prepareAMQPChannel();
        $this->multipleConsumer->setContext('foo');
        
        $queuesProvider = $this->prepareQueuesProvider();
        $queuesProvider->expects($this->once())
            ->method('getQueues')
            ->will($this->returnValue(
                array(
                    'queue_foo' => array()
                )
            ));
        
        $this->multipleConsumer->setQueuesProvider($queuesProvider);
        
        $reflectionClass = new \ReflectionClass(get_class($this->multipleConsumer));
        $reflectionMethod = $reflectionClass->getMethod('mergeQueues');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->multipleConsumer);        
    }

    /**
     * Check queues provider works well with static queues together
     *
     * @dataProvider processMessageProvider
     */
    public function testQueuesProviderAndStaticQueuesTogether($processFlag, $expectedMethod, $expectedRequeue = null)
    {
        $callback = $this->prepareCallback($processFlag);

        $this->multipleConsumer->setQueues(
            array(
                'test-1' => array('callback' => $callback),
                'test-2' => array('callback' => $callback)
            )
        );

        $queuesProvider = $this->prepareQueuesProvider();
        $queuesProvider->expects($this->once())
            ->method('getQueues')
            ->will($this->returnValue(
                array(
                    'test-3' => array('callback' => $callback),
                    'test-4' => array('callback' => $callback)
                )
            ));

        $this->multipleConsumer->setQueuesProvider($queuesProvider);

        /**
         * We don't test consume method, which merges queues by calling $this->setupConsumer();
         * So we need to invoke it manually
         */
        $reflectionClass = new \ReflectionClass(get_class($this->multipleConsumer));
        $reflectionMethod = $reflectionClass->getMethod('mergeQueues');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->multipleConsumer);

        $this->prepareAMQPChannelExpectations($expectedMethod, $expectedRequeue);

        // Create a default message
        $amqpMessage = new AMQPMessage('foo body');
        $amqpMessage->delivery_info['channel'] = $this->amqpChannel;
        $amqpMessage->delivery_info['delivery_tag'] = 0;

        $this->multipleConsumer->processQueueMessage('test-1', $amqpMessage);
        $this->multipleConsumer->processQueueMessage('test-2', $amqpMessage);
        $this->multipleConsumer->processQueueMessage('test-3', $amqpMessage);
        $this->multipleConsumer->processQueueMessage('test-4', $amqpMessage);
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

    /**
     * Preparing AMQP Connection
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPConnection
     */
    private function prepareAMQPConnection()
    {
        return $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Preparing AMQP Connection
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    private function prepareAMQPChannel()
    {
        return $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Preparing QueuesProviderInterface instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|QueuesProviderInterface
     */
    private function prepareQueuesProvider()
    {
        return $this->getMockBuilder('\OldSound\RabbitMqBundle\Provider\QueuesProviderInterface')
            ->getMock();
    }

    /**
     * Preparing AMQP Channel Expectations
     *
     * @param $expectedMethod
     * @param $expectedRequeue
     *
     * @return void
     */
    private function prepareAMQPChannelExpectations($expectedMethod, $expectedRequeue)
    {
        $this->amqpChannel->expects($this->any())
            ->method('basic_reject')
            ->will($this->returnCallback(function($delivery_tag, $requeue) use ($expectedMethod, $expectedRequeue) {
                \PHPUnit_Framework_Assert::assertSame($expectedMethod, 'basic_reject'); // Check if this function should be called.
                \PHPUnit_Framework_Assert::assertSame($requeue, $expectedRequeue); // Check if the message should be requeued.
            }));

        $this->amqpChannel->expects($this->any())
            ->method('basic_ack')
            ->will($this->returnCallback(function($delivery_tag) use ($expectedMethod) {
                \PHPUnit_Framework_Assert::assertSame($expectedMethod, 'basic_ack'); // Check if this function should be called.
            }));
    }

    /**
     * Prepare callback
     *
     * @param $processFlag
     * @return callable
     */
    private function prepareCallback($processFlag)
    {
        return function($msg) use (&$lastQueue, $processFlag) {
            return $processFlag;
        };
    }
} 