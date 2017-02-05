<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{   
    protected function getConsumer($amqpConnection, $amqpChannel)
    {
        return new Consumer($amqpConnection, $amqpChannel);
    }

    protected function prepareAMQPConnection()
    {
        return $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
                        ->disableOriginalConstructor()
                        ->getMock();
    }

    protected function prepareAMQPChannel()
    {
        return $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
                        ->disableOriginalConstructor()
                        ->getMock();
    }

    /**
     * Check if the message is requeued or not correctly.
     *
     * @dataProvider processMessageProvider
     */
    public function testProcessMessage($processFlag, $expectedMethod, $expectedRequeue = null)
    {
        $amqpConnection = $this->prepareAMQPConnection();
        $amqpChannel = $this->prepareAMQPChannel();
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);

        $callbackFunction = function() use ($processFlag) { return $processFlag; }; // Create a callback function with a return value set by the data provider.
        $consumer->setCallback($callbackFunction);

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
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->getMock();
        $consumer->setEventDispatcher($eventDispatcher);

        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->withConsecutive(
                array(BeforeProcessingMessageEvent::NAME, new BeforeProcessingMessageEvent($consumer, $amqpMessage)),
                array(AfterProcessingMessageEvent::NAME, new AfterProcessingMessageEvent($consumer, $amqpMessage))
            )
            ->willReturn(true);
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
        );
    }

    /**
     * @return array
     */
    public function consumeProvider()
    {
        $testCases[ "All ok 4 callbacks"] =  array(
            array(
                "messages" => array(
                    "msgCallback1",
                    "msgCallback2",
                    "msgCallback3",
                    "msgCallback4",
                )
            )
        );

        $testCases[ "No callbacks"] =  array(
            array(
                "messages" => array(
                )
            )
        );

        return $testCases;
    }

    /**
     * @dataProvider consumeProvider
     *
     * @param $data
     */
    public function testConsume($data)
    {
        $consumerCallBacks = $data['messages'];

        // set up amqp connection
        $amqpConnection = $this->prepareAMQPConnection();
        // set up amqp channel
        $amqpChannel = $this->prepareAMQPChannel();
        $amqpChannel->expects($this->atLeastOnce())
            ->method('getChannelId')
            ->with()
            ->willReturn(true);
        $amqpChannel->expects($this->once())
            ->method('basic_consume')
            ->withAnyParameters()
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $amqpChannel->callbacks = $consumerCallBacks;

        /**
         * Mock wait method and use a callback to remove one element each time from callbacks
         * This will simulate a basic consumer consume with provided messages count
         */
        $amqpChannel->expects($this->exactly(count($consumerCallBacks)))
            ->method('wait')
            ->with(null, false, $consumer->getIdleTimeout())
            ->will(
                $this->returnCallback(
                    function () use ($amqpChannel) {
                        /** remove an element on each loop like ... simulate an ACK */
                        array_splice($amqpChannel->callbacks, 0, 1);
                    })
            );

        // set up event dispatcher
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects($this->exactly(count($consumerCallBacks)))
            ->method('dispatch')
            ->with(OnConsumeEvent::NAME, $this->isInstanceOf('OldSound\RabbitMqBundle\Event\OnConsumeEvent'))
            ->willReturn(true);

        $consumer->setEventDispatcher($eventDispatcher);
        $consumer->consume(1);
    }

    public function testIdleTimeoutExitCode()
    {
        // set up amqp connection
        $amqpConnection = $this->prepareAMQPConnection();
        // set up amqp channel
        $amqpChannel = $this->prepareAMQPChannel();
        $amqpChannel->expects($this->atLeastOnce())
            ->method('getChannelId')
            ->with()
            ->willReturn(true);
        $amqpChannel->expects($this->once())
            ->method('basic_consume')
            ->withAnyParameters()
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $consumer->setIdleTimeoutExitCode(2);
        $amqpChannel->callbacks = array('idle_timeout_exit_code');

        $amqpChannel->expects($this->exactly(1))
            ->method('wait')
            ->with(null, false, $consumer->getIdleTimeout())
            ->willThrowException(new AMQPTimeoutException());

        $this->assertTrue(2 == $consumer->consume(1));
    }

    public function testShouldAllowContinueConsumptionAfterIdleTimeout()
    {
        // set up amqp connection
        $amqpConnection = $this->prepareAMQPConnection();
        // set up amqp channel
        $amqpChannel = $this->prepareAMQPChannel();
        $amqpChannel->expects($this->atLeastOnce())
            ->method('getChannelId')
            ->with()
            ->willReturn(true);
        $amqpChannel->expects($this->once())
            ->method('basic_consume')
            ->withAnyParameters()
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $consumer->setIdleTimeout(2);
        $amqpChannel->callbacks = array('idle_timeout_exit_code');

        $amqpChannel->expects($this->exactly(2))
            ->method('wait')
            ->with(null, false, $consumer->getIdleTimeout())
            ->willThrowException(new AMQPTimeoutException());

        // set up event dispatcher
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(OnIdleEvent::NAME, $this->isInstanceOf('OldSound\RabbitMqBundle\Event\OnIdleEvent'))
            ->willReturnCallback(function($eventName, OnIdleEvent $event) {
                $event->setForceStop(false);
            });
        $eventDispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(OnIdleEvent::NAME, $this->isInstanceOf('OldSound\RabbitMqBundle\Event\OnIdleEvent'))
            ->willReturn(function($eventName, OnIdleEvent $event) {
                $event->setForceStop(true);
        });

        $consumer->setEventDispatcher($eventDispatcher);

        $this->setExpectedException('PhpAmqpLib\Exception\AMQPTimeoutException');
        $consumer->consume(10);
    }

    public function testGracefulMaxExecutionTimeoutExitCode()
    {
        // set up amqp connection
        $amqpConnection = $this->prepareAMQPConnection();
        // set up amqp channel
        $amqpChannel = $this->prepareAMQPChannel();
        $amqpChannel->expects($this->atLeastOnce())
            ->method('getChannelId')
            ->with()
            ->willReturn(true);
        $amqpChannel->expects($this->once())
            ->method('basic_consume')
            ->withAnyParameters()
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);

        $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(60);
        $consumer->setGracefulMaxExecutionTimeoutExitCode(10);
        $amqpChannel->callbacks = array('graceful_max_execution_timeout_test');

        $amqpChannel->expects($this->exactly(1))
            ->method('wait')
            ->willThrowException(new AMQPTimeoutException());

        $this->assertSame(10, $consumer->consume(1));
    }

    public function testGracefulMaxExecutionWontWaitIfPastTheTimeout()
    {
        // set up amqp connection
        $amqpConnection = $this->prepareAMQPConnection();
        // set up amqp channel
        $amqpChannel = $this->prepareAMQPChannel();
        $amqpChannel->expects($this->atLeastOnce())
            ->method('getChannelId')
            ->with()
            ->willReturn(true);
        $amqpChannel->expects($this->once())
            ->method('basic_consume')
            ->withAnyParameters()
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);

        $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(0);
        $amqpChannel->callbacks = array('graceful_max_execution_timeout_test');

        $amqpChannel->expects($this->never())
            ->method('wait');

        $consumer->consume(1);
    }
}
