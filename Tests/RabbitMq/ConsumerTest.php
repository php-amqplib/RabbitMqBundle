<?php

use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\DynamicConsumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// Dataset: run all consumer behaviour tests for both Consumer and DynamicConsumer
dataset('consumer_classes', [
    'Consumer'        => [Consumer::class],
    'DynamicConsumer' => [DynamicConsumer::class],
]);

test('process message with various flags', function (string $consumerClass, mixed $processFlag, ?string $expectedMethod, ?bool $expectedRequeue) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
    $consumer       = new $consumerClass($amqpConnection, $amqpChannel);

    $consumer->setCallback(static fn () => $processFlag);

    $amqpMessage = new AMQPMessage('foo body');
    $amqpMessage->setChannel($amqpChannel);
    $amqpMessage->setDeliveryTag(0);

    if ($expectedMethod) {
        $amqpChannel->method('basic_reject')
            ->willReturnCallback(function ($tag, $requeue) use ($expectedMethod, $expectedRequeue) {
                expect($expectedMethod)->toBe('basic_reject');
                expect($requeue)->toBe($expectedRequeue);
            });

        $amqpChannel->method('basic_ack')
            ->willReturnCallback(function () use ($expectedMethod) {
                expect($expectedMethod)->toBe('basic_ack');
            });
    } else {
        $amqpChannel->expects($this->never())->method('basic_reject');
        $amqpChannel->expects($this->never())->method('basic_ack');
        $amqpChannel->expects($this->never())->method('basic_nack');
    }

    $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();
    $consumer->setEventDispatcher($eventDispatcher);

    $callIndex = 0;
    $eventDispatcher->method('dispatch')
        ->willReturnCallback(function ($event) use (&$callIndex, $consumer, $amqpMessage) {
            if ($callIndex === 0) {
                expect($event)->toEqual(new BeforeProcessingMessageEvent($consumer, $amqpMessage));
            } else {
                expect($event)->toEqual(new AfterProcessingMessageEvent($consumer, $amqpMessage));
            }
            $callIndex++;

            return $event;
        });

    $consumer->processMessage($amqpMessage);
})->with('consumer_classes')->with([
    'ack on null return'              => [null,                              'basic_ack',    null],
    'ack on true return'              => [true,                              'basic_ack',    null],
    'reject and requeue on false'     => [false,                             'basic_reject', true],
    'ack on MSG_ACK'                  => [ConsumerInterface::MSG_ACK,        'basic_ack',    null],
    'reject and requeue on MSG_REJECT_REQUEUE' => [ConsumerInterface::MSG_REJECT_REQUEUE, 'basic_reject', true],
    'reject and drop on MSG_REJECT'   => [ConsumerInterface::MSG_REJECT,     'basic_reject', false],
    'no ack on MSG_ACK_SENT'          => [ConsumerInterface::MSG_ACK_SENT,   null,           null],
]);

test('consume dispatches consume event and stops when not consuming', function (string $consumerClass, array $data) {
    $messageCount   = count($data['messages']);
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $amqpChannel->method('getChannelId')->willReturn(true);
    $amqpChannel->expects($this->once())->method('basic_consume')->withAnyParameters()->willReturn(true);
    // The loop runs exactly once: 1st is_consuming → true (enter loop), wait() runs,
    // 2nd is_consuming → false (exit loop). messageCount only affects willReturn values.
    $amqpChannel->expects(self::exactly(2))
        ->method('is_consuming')
        ->willReturnOnConsecutiveCalls(true, false);

    $consumer = new $consumerClass($amqpConnection, $amqpChannel);
    $consumer->disableAutoSetupFabric();
    $consumer->setChannel($amqpChannel);

    $amqpChannel->expects(self::exactly(1))
        ->method('wait')
        ->with(null, false, $consumer->getIdleTimeout())
        ->willReturn(true);

    $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();
    $eventDispatcher->expects(self::exactly(1))
        ->method('dispatch')
        ->with($this->isInstanceOf(OnConsumeEvent::class), OnConsumeEvent::NAME)
        ->willReturn($this->isInstanceOf(OnConsumeEvent::class));

    $consumer->setEventDispatcher($eventDispatcher);
    $consumer->consume(1);
})->with('consumer_classes')->with([
    'with 4 messages' => [['messages' => ['msg1', 'msg2', 'msg3', 'msg4']]],
    'with no messages' => [['messages' => []]],
]);

test('process message stops consuming when memory limit is almost reached', function (string $consumerClass) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $consumer = $this->getMockBuilder($consumerClass)
        ->setConstructorArgs([$amqpConnection, $amqpChannel])
        ->onlyMethods(['isRamAlmostOverloaded', 'stopConsuming'])
        ->getMock();

    $consumer->setCallback(static fn () => true);
    $consumer->setMemoryLimit(128);

    $consumer->expects($this->once())->method('isRamAlmostOverloaded')->willReturn(true);
    $consumer->expects($this->once())->method('stopConsuming');

    $amqpMessage = new AMQPMessage('foo body');
    $amqpMessage->setChannel($amqpChannel);
    $amqpMessage->setDeliveryTag(0);

    $consumer->processMessage($amqpMessage);
})->with('consumer_classes');

test('consume exits without waiting when memory limit is already exceeded', function (string $consumerClass) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $amqpChannel->method('getChannelId')->willReturn(true);
    $amqpChannel->expects($this->once())->method('basic_consume')->withAnyParameters()->willReturn(true);
    $amqpChannel->expects(self::exactly(2))
        ->method('is_consuming')
        ->willReturnOnConsecutiveCalls(true, false);
    $amqpChannel->expects($this->once())->method('basic_cancel');
    // The consumer is already cancelled, so wait() would block indefinitely.
    $amqpChannel->expects($this->never())->method('wait');

    $consumer = $this->getMockBuilder($consumerClass)
        ->setConstructorArgs([$amqpConnection, $amqpChannel])
        ->onlyMethods(['isRamAlmostOverloaded'])
        ->getMock();
    $consumer->method('isRamAlmostOverloaded')->willReturn(true);

    $consumer->disableAutoSetupFabric();
    $consumer->setChannel($amqpChannel);
    $consumer->setMemoryLimit(1);

    expect($consumer->consume(1))->toBe(0);
})->with('consumer_classes');

test('idle timeout returns configured exit code', function (string $consumerClass) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $amqpChannel->method('getChannelId')->willReturn(true);
    $amqpChannel->expects($this->once())->method('basic_consume')->withAnyParameters()->willReturn(true);
    $amqpChannel->method('is_consuming')->willReturn(true);

    $consumer = new $consumerClass($amqpConnection, $amqpChannel);
    $consumer->disableAutoSetupFabric();
    $consumer->setChannel($amqpChannel);
    $consumer->setIdleTimeout(60);
    $consumer->setIdleTimeoutExitCode(2);

    $amqpChannel->expects($this->exactly(1))
        ->method('wait')
        ->with(null, false, $consumer->getIdleTimeout())
        ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
            $consumer->setLastActivityDateTime(new \DateTime("-$waitTimeout seconds"));
            throw new AMQPTimeoutException();
        });

    expect($consumer->consume(1))->toBe(2);
})->with('consumer_classes');

test('consumption continues after idle timeout when force stop is false', function (string $consumerClass) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $amqpChannel->method('getChannelId')->willReturn(true);
    $amqpChannel->expects($this->once())->method('basic_consume')->withAnyParameters()->willReturn(true);
    $amqpChannel->method('is_consuming')->willReturn(true);

    $consumer = new $consumerClass($amqpConnection, $amqpChannel);
    $consumer->disableAutoSetupFabric();
    $consumer->setChannel($amqpChannel);
    $consumer->setIdleTimeout(2);

    $amqpChannel->expects($this->exactly(2))
        ->method('wait')
        ->with(null, false, $consumer->getIdleTimeout())
        ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
            $consumer->setLastActivityDateTime(new \DateTime("-$waitTimeout seconds"));
            throw new AMQPTimeoutException();
        });

    $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();

    $dispatchCallIndex = 0;
    $eventDispatcher->expects($this->exactly(4))
        ->method('dispatch')
        ->willReturnCallback(function ($event, $eventName) use (&$dispatchCallIndex) {
            if ($dispatchCallIndex === 1) {
                expect($event)->toBeInstanceOf(OnIdleEvent::class);
                $event->setForceStop(false);
            } elseif ($dispatchCallIndex === 3) {
                expect($event)->toBeInstanceOf(OnIdleEvent::class);
                $event->setForceStop(true);
            }
            $dispatchCallIndex++;

            return $event;
        });

    $consumer->setEventDispatcher($eventDispatcher);

    expect(fn () => $consumer->consume(10))->toThrow(AMQPTimeoutException::class);
})->with('consumer_classes');

test('graceful max execution will not wait if past timeout', function (string $consumerClass) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $amqpChannel->method('getChannelId')->willReturn(true);
    $amqpChannel->expects($this->once())->method('basic_consume')->withAnyParameters()->willReturn(true);
    $amqpChannel->method('is_consuming')->willReturn(true);

    $consumer = new $consumerClass($amqpConnection, $amqpChannel);
    $consumer->disableAutoSetupFabric();
    $consumer->setChannel($amqpChannel);
    $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(0);

    $amqpChannel->expects($this->never())->method('wait');

    $consumer->consume(1);
})->with('consumer_classes');

test('timeout wait uses minimum of timeout wait and other timeouts', function (string $consumerClass) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $amqpChannel->method('getChannelId')->willReturn(true);
    $amqpChannel->expects($this->once())->method('basic_consume')->withAnyParameters()->willReturn(true);
    $amqpChannel->method('is_consuming')->willReturn(true);

    $consumer = new $consumerClass($amqpConnection, $amqpChannel);
    $consumer->disableAutoSetupFabric();
    $consumer->setChannel($amqpChannel);
    $consumer->setTimeoutWait(30);
    $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(60);
    $consumer->setIdleTimeout(50);

    $amqpChannel->expects($this->exactly(2))
        ->method('wait')
        ->with(null, false, $this->lessThanOrEqual($consumer->getTimeoutWait()))
        ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
            $consumer->setGracefulMaxExecutionDateTime(
                $consumer->getGracefulMaxExecutionDateTime()->modify("-$waitTimeout seconds")
            );
            $consumer->setLastActivityDateTime(new \DateTime());
            throw new AMQPTimeoutException();
        });

    $consumer->consume(1);
})->with('consumer_classes');

test('timeout wait will not wait past idle timeout', function (string $consumerClass) {
    $amqpConnection = $this->getMockBuilder(AMQPStreamConnection::class)->disableOriginalConstructor()->getMock();
    $amqpChannel    = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();

    $amqpChannel->method('getChannelId')->willReturn(true);
    $amqpChannel->expects($this->once())->method('basic_consume')->withAnyParameters()->willReturn(true);
    $amqpChannel->method('is_consuming')->willReturn(true);

    $consumer = new $consumerClass($amqpConnection, $amqpChannel);
    $consumer->disableAutoSetupFabric();
    $consumer->setChannel($amqpChannel);
    $consumer->setTimeoutWait(20);
    $consumer->setIdleTimeout(10);
    $consumer->setIdleTimeoutExitCode(2);

    $amqpChannel->expects($this->once())
        ->method('wait')
        ->with(null, false, 10)
        ->willReturnCallback(function ($allowedMethods, $nonBlocking, $waitTimeout) use ($consumer) {
            $consumer->setLastActivityDateTime(new \DateTime("-$waitTimeout seconds"));
            throw new AMQPTimeoutException();
        });

    expect($consumer->consume(1))->toBe(2);
})->with('consumer_classes');
