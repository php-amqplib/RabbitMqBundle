<?php

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

beforeEach(function () {
    $this->makeChannel = function (): AMQPChannel {
        $channel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channel->method('getChannelId')->willReturn(1);

        return $channel;
    };

    $this->channel = ($this->makeChannel)();

    $this->connection = $this->getMockBuilder(AbstractConnection::class)
        ->disableOriginalConstructor()
        ->getMock();
    $this->connection->method('connectOnConstruct')->willReturn(false);
    $this->connection->method('channel')->willReturn($this->channel);

    $this->producer = new Producer($this->connection, $this->channel);
    $this->producer->disableAutoSetupFabric();
    $this->producer->setExchangeOptions(['name' => 'test_exchange', 'type' => 'direct']);
});

test('publisher confirms are off by default and the channel is untouched', function () {
    $this->channel->expects($this->never())->method('confirm_select');
    $this->channel->expects($this->never())->method('wait_for_pending_acks');
    $this->channel->expects($this->once())->method('basic_publish');

    expect($this->producer->isConfirmSelect())->toBeFalse();
    expect($this->producer->publish('body'))->toBeTrue();
});

test('the confirmation timeout defaults to ten seconds', function () {
    expect($this->producer->getConfirmationTimeout())->toBe(10.0);
});

test('confirm mode is enabled once and awaited on every publish', function () {
    $this->producer->setConfirmSelect(true)->setConfirmationTimeout(2.0);

    $this->channel->expects($this->once())->method('confirm_select');
    $this->channel->expects($this->once())->method('set_ack_handler');
    $this->channel->expects($this->once())->method('set_nack_handler');
    $this->channel->expects($this->exactly(2))
        ->method('wait_for_pending_acks')
        ->with(2.0);

    $this->producer->publish('one');
    $this->producer->publish('two');
});

test('publish returns false when the broker nacks the message', function () {
    $handlers = [];
    $this->channel->method('set_ack_handler')
        ->willReturnCallback(function (callable $h) use (&$handlers) {
            $handlers['ack'] = $h;
        });
    $this->channel->method('set_nack_handler')
        ->willReturnCallback(function (callable $h) use (&$handlers) {
            $handlers['nack'] = $h;
        });
    $this->channel->method('wait_for_pending_acks')
        ->willReturnCallback(function () use (&$handlers) {
            $handlers['nack'](new AMQPMessage(''));
        });

    $this->producer->setConfirmSelect(true);

    expect($this->producer->publish('body'))->toBeFalse();
});

test('a nacked publish does not poison the following publish', function () {
    $handlers = [];
    $this->channel->method('set_ack_handler')
        ->willReturnCallback(function (callable $h) use (&$handlers) {
            $handlers['ack'] = $h;
        });
    $this->channel->method('set_nack_handler')
        ->willReturnCallback(function (callable $h) use (&$handlers) {
            $handlers['nack'] = $h;
        });

    $sequence = ['nack', 'ack'];
    $this->channel->method('wait_for_pending_acks')
        ->willReturnCallback(function () use (&$handlers, &$sequence) {
            $handlers[array_shift($sequence)](new AMQPMessage(''));
        });

    $this->producer->setConfirmSelect(true);

    expect($this->producer->publish('first'))->toBeFalse();
    expect($this->producer->publish('second'))->toBeTrue();
});

test('confirm mode is re-enabled after the channel is replaced', function () {
    $this->producer->setConfirmSelect(true);
    $this->channel->expects($this->once())->method('confirm_select');

    $this->producer->publish('one');

    $replacement = ($this->makeChannel)();
    $replacement->expects($this->once())->method('confirm_select');

    $this->producer->setChannel($replacement);
    $this->producer->publish('two');
});

test('a confirmation timeout propagates as AMQPTimeoutException', function () {
    $this->channel->method('wait_for_pending_acks')
        ->willThrowException(new AMQPTimeoutException('timeout'));

    $this->producer->setConfirmSelect(true);

    expect(fn () => $this->producer->publish('body'))->toThrow(AMQPTimeoutException::class);
});
