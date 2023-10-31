<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

class ConsumerTest extends TestCase
{
    protected function getConsumer($amqpConnection, $amqpChannel)
    {
        return new Consumer($amqpConnection, $amqpChannel);
    }

    protected function prepareAMQPConnection()
    {
        return $this->getMockBuilder(AMQPStreamConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function prepareAMQPChannel()
    {
        return $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Check if the message is requeued or not correctly.
     *
     * @dataProvider processMessageProvider
     */
    public function testProcessMessage($processFlag, $expectedMethod = null, $expectedRequeue = null)
    {
        $amqpConnection = $this->prepareAMQPConnection();
        $amqpChannel = $this->prepareAMQPChannel();
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);

        $callbackFunction = function () use ($processFlag) {
            return $processFlag;
        }; // Create a callback function with a return value set by the data provider.
        $consumer->setCallback($callbackFunction);

        // Create a default message
        $amqpMessage = new AMQPMessage('foo body');
        $amqpMessage->setChannel($amqpChannel);
        $amqpMessage->setDeliveryTag(0);

        if ($expectedMethod) {
            $amqpChannel->expects($this->any())
                ->method('basic_reject')
                ->will($this->returnCallback(function ($delivery_tag, $requeue) use ($expectedMethod, $expectedRequeue) {
                    Assert::assertSame($expectedMethod, 'basic_reject'); // Check if this function should be called.
                    Assert::assertSame($requeue, $expectedRequeue); // Check if the message should be requeued.
                }));

            $amqpChannel->expects($this->any())
                ->method('basic_ack')
                ->will($this->returnCallback(function ($delivery_tag) use ($expectedMethod) {
                    Assert::assertSame($expectedMethod, 'basic_ack'); // Check if this function should be called.
                }));
        } else {
            $amqpChannel->expects($this->never())->method('basic_reject');
            $amqpChannel->expects($this->never())->method('basic_ack');
            $amqpChannel->expects($this->never())->method('basic_nack');
        }

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $consumer->setEventDispatcher($eventDispatcher);

        $eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->withConsecutive(
                [new BeforeProcessingMessageEvent($consumer, $amqpMessage), BeforeProcessingMessageEvent::NAME],
                [new AfterProcessingMessageEvent($consumer, $amqpMessage), AfterProcessingMessageEvent::NAME]
            )
            ->willReturnOnConsecutiveCalls(
                new BeforeProcessingMessageEvent($consumer, $amqpMessage),
                new AfterProcessingMessageEvent($consumer, $amqpMessage)
            );

        $consumer->processMessage($amqpMessage);
    }

    public function processMessageProvider()
    {
        return [
            [null, 'basic_ack'], // Remove message from queue only if callback return not false
            [true, 'basic_ack'], // Remove message from queue only if callback return not false
            [false, 'basic_reject', true], // Reject and requeue message to RabbitMQ
            [ConsumerInterface::MSG_ACK, 'basic_ack'], // Remove message from queue only if callback return not false
            [ConsumerInterface::MSG_REJECT_REQUEUE, 'basic_reject', true], // Reject and requeue message to RabbitMQ
            [ConsumerInterface::MSG_REJECT, 'basic_reject', false], // Reject and drop
            [ConsumerInterface::MSG_ACK_SENT], // ack not sent by the consumer but should be sent by the implementer of ConsumerInterface
        ];
    }

    /**
     * @return array
     */
    public function consumeProvider()
    {
        $testCases["All ok 4 callbacks"] = [
            [
                "messages" => [
                    "msgCallback1",
                    "msgCallback2",
                    "msgCallback3",
                    "msgCallback4",
                ],
            ],
        ];

        $testCases["No callbacks"] = [
            [
                "messages" => [],
            ],
        ];

        return $testCases;
    }

    /**
     * @dataProvider consumeProvider
     *
     * @param array $data
     */
    public function testConsume(array $data)
    {
        $messageCount = count($data['messages']);

        // set up amqp connection
        $amqpConnection = $this->prepareAMQPConnection();
        // set up amqp channel
        $amqpChannel = $this->prepareAMQPChannel();
        $amqpChannel->expects($this->atLeastOnce())
            ->method('getChannelId')
            ->with()
            ->willReturn(true);
        $amqpChannel
            ->expects($this->once())
            ->method('basic_consume')
            ->withAnyParameters()
            ->willReturn(true);
        $amqpChannel
            ->expects(self::exactly(2))
            ->method('is_consuming')
            ->willReturnOnConsecutiveCalls(array_merge(array_fill(0, $messageCount, true), [false]));

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);

        /**
         * Mock wait method and use a callback to remove one element each time from callbacks
         * This will simulate a basic consumer consume with provided messages count
         */
        $amqpChannel
            ->expects(self::exactly(1))
            ->method('wait')
            ->with(null, false, $consumer->getIdleTimeout())
            ->willReturn(true);

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher
            ->expects(self::exactly(1))
            ->method('dispatch')
            ->with($this->isInstanceOf(OnConsumeEvent::class), OnConsumeEvent::NAME)
            ->willReturn($this->isInstanceOf(OnConsumeEvent::class));

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
        $amqpChannel
            ->expects($this->any())
            ->method('is_consuming')
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $consumer->setIdleTimeout(60);
        $consumer->setIdleTimeoutExitCode(2);

        $amqpChannel->expects($this->exactly(1))
            ->method('wait')
            ->with(null, false, $consumer->getIdleTimeout())
            ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
                // simulate time passing by moving the last activity date time
                $consumer->setLastActivityDateTime(new \DateTime("-$waitTimeout seconds"));
                throw new AMQPTimeoutException();
            });

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
        $amqpChannel
            ->expects($this->any())
            ->method('is_consuming')
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $consumer->setIdleTimeout(2);

        $amqpChannel->expects($this->exactly(2))
            ->method('wait')
            ->with(null, false, $consumer->getIdleTimeout())
            ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
                // simulate time passing by moving the last activity date time
                $consumer->setLastActivityDateTime(new \DateTime("-$waitTimeout seconds"));
                throw new AMQPTimeoutException();
            });

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->isInstanceOf(OnConsumeEvent::class),
                    OnConsumeEvent::NAME,
                ],
                [
                    $this->callback(function (OnIdleEvent $event) {
                        $event->setForceStop(false);

                        return true;
                    }),
                    OnIdleEvent::NAME,
                ],
                [
                    $this->isInstanceOf(OnConsumeEvent::class),
                    OnConsumeEvent::NAME,
                ],
                [
                    $this->callback(function (OnIdleEvent $event) {
                        $event->setForceStop(true);

                        return true;
                    }),
                    OnIdleEvent::NAME,
                ]
            );

        $consumer->setEventDispatcher($eventDispatcher);

        $this->expectException(AMQPTimeoutException::class);
        $consumer->consume(10);
    }
    //TODO: try to understand the logic and fix the test
    //    public function testGracefulMaxExecutionTimeoutExitCode()
    //    {
    //        // set up amqp connection
    //        $amqpConnection = $this->prepareAMQPConnection();
    //        // set up amqp channel
    //        $amqpChannel = $this->prepareAMQPChannel();
    //        $amqpChannel->expects($this->atLeastOnce())
    //            ->method('getChannelId')
    //            ->with()
    //            ->willReturn(true);
    //        $amqpChannel->expects($this->once())
    //            ->method('basic_consume')
    //            ->withAnyParameters()
    //            ->willReturn(true);
    //        $amqpChannel
    //            ->expects($this->any())
    //            ->method('is_consuming')
    //            ->willReturn(true);
    //
    //        // set up consumer
    //        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
    //        // disable autosetup fabric so we do not mock more objects
    //        $consumer->disableAutoSetupFabric();
    //        $consumer->setChannel($amqpChannel);
    //        $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(60);
    //        $consumer->setGracefulMaxExecutionTimeoutExitCode(10);
    //
    //        $amqpChannel->expects($this->exactly(1))
    //            ->method('wait')
    //            ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
    //                // simulate time passing by moving the max execution date time
    //                $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture($waitTimeout * -1);
    //                throw new AMQPTimeoutException();
    //            });
    //
    //        $this->assertSame(10, $consumer->consume(1));
    //    }

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
        $amqpChannel
            ->expects($this->any())
            ->method('is_consuming')
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);

        $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(0);

        $amqpChannel
            ->expects($this->never())
            ->method('wait');

        $consumer->consume(1);
    }

    public function testTimeoutWait()
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
        $amqpChannel
            ->expects($this->any())
            ->method('is_consuming')
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $consumer->setTimeoutWait(30);
        $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(60);
        $consumer->setIdleTimeout(50);

        $amqpChannel->expects($this->exactly(2))
            ->method('wait')
            ->with(null, false, $this->LessThanOrEqual($consumer->getTimeoutWait()))
            ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
                // ensure max execution date time "counts down"
                $consumer->setGracefulMaxExecutionDateTime(
                    $consumer->getGracefulMaxExecutionDateTime()->modify("-$waitTimeout seconds")
                );
                // ensure last activity just occurred so idle timeout is not reached
                $consumer->setLastActivityDateTime(new \DateTime());
                throw new AMQPTimeoutException();
            });

        $consumer->consume(1);
    }
    //TODO: try to understand the logic and fix the test
    //    public function testTimeoutWaitWontWaitPastGracefulMaxExecutionTimeout()
    //    {
    //        // set up amqp connection
    //        $amqpConnection = $this->prepareAMQPConnection();
    //        // set up amqp channel
    //        $amqpChannel = $this->prepareAMQPChannel();
    //        $amqpChannel->expects($this->atLeastOnce())
    //            ->method('getChannelId')
    //            ->with()
    //            ->willReturn(true);
    //        $amqpChannel->expects($this->once())
    //            ->method('basic_consume')
    //            ->withAnyParameters()
    //            ->willReturn(true);
    //        $amqpChannel
    //            ->expects($this->any())
    //            ->method('is_consuming')
    //            ->willReturn(true);
    //
    //        // set up consumer
    //        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
    //        // disable autosetup fabric so we do not mock more objects
    //        $consumer->disableAutoSetupFabric();
    //        $consumer->setChannel($amqpChannel);
    //        $consumer->setTimeoutWait(20);
    //
    //        $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(10);
    //
    //        $amqpChannel->expects($this->once())
    //            ->method('wait')
    //            ->with(null, false, $consumer->getGracefulMaxExecutionDateTime()->diff(new \DateTime())->s)
    //            ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
    //                // simulate time passing by moving the max execution date time
    //                $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture($waitTimeout * -1);
    //                throw new AMQPTimeoutException();
    //            });
    //
    //        $consumer->consume(1);
    //    }

    public function testTimeoutWaitWontWaitPastIdleTimeout()
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
        $amqpChannel
            ->expects($this->any())
            ->method('is_consuming')
            ->willReturn(true);

        // set up consumer
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $consumer->setTimeoutWait(20);
        $consumer->setIdleTimeout(10);
        $consumer->setIdleTimeoutExitCode(2);

        $amqpChannel->expects($this->once())
            ->method('wait')
            ->with(null, false, 10)
            ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
                // simulate time passing by moving the last activity date time
                $consumer->setLastActivityDateTime(new \DateTime("-$waitTimeout seconds"));
                throw new AMQPTimeoutException();
            });

        $this->assertEquals(2, $consumer->consume(1));
    }
}
