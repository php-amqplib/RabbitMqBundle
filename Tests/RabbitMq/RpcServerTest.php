<?php

use OldSound\RabbitMqBundle\RabbitMq\RpcServer;
use PhpAmqpLib\Message\AMQPMessage;

test('process message uses custom serializer when set', function () {
    $server = $this->getMockBuilder(RpcServer::class)
        ->onlyMethods(['sendReply', 'maybeStopConsumer'])
        ->disableOriginalConstructor()
        ->getMock();

    $message = $this->getMockBuilder(AMQPMessage::class)
        ->onlyMethods(['get'])
        ->getMock();

    $channel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->disableOriginalConstructor()
        ->getMock();

    $message->setChannel($channel);
    $message->setDeliveryTag(0);

    $server->setCallback(static fn () => 'message');

    $serializer = $this->getMockBuilder('\Symfony\Component\Serializer\SerializerInterface')->getMock();
    $serializer->expects($this->once())->method('serialize')->with('message', 'json');

    $server->setSerializer(function ($data) use ($serializer) {
        $serializer->serialize($data, 'json');
    });

    $server->processMessage($message);
});

test('process message stops consuming when memory limit is almost reached', function () {
    $server = $this->getMockBuilder(RpcServer::class)
        ->onlyMethods(['sendReply', 'isRamAlmostOverloaded', 'stopConsuming'])
        ->disableOriginalConstructor()
        ->getMock();

    $message = $this->getMockBuilder(AMQPMessage::class)
        ->onlyMethods(['get'])
        ->getMock();

    $channel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->disableOriginalConstructor()
        ->getMock();

    $message->setChannel($channel);
    $message->setDeliveryTag(0);

    $server->setCallback(static fn () => 'message');
    $server->setMemoryLimit(1);

    $server->expects($this->once())->method('isRamAlmostOverloaded')->willReturn(true);
    $server->expects($this->once())->method('stopConsuming');

    $server->processMessage($message);
});

test('process message does not check memory when no limit is set', function () {
    $server = $this->getMockBuilder(RpcServer::class)
        ->onlyMethods(['sendReply', 'isRamAlmostOverloaded', 'stopConsuming'])
        ->disableOriginalConstructor()
        ->getMock();

    $message = $this->getMockBuilder(AMQPMessage::class)
        ->onlyMethods(['get'])
        ->getMock();

    $channel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->disableOriginalConstructor()
        ->getMock();

    $message->setChannel($channel);
    $message->setDeliveryTag(0);

    $server->setCallback(static fn () => 'message');

    $server->expects($this->never())->method('isRamAlmostOverloaded');
    $server->expects($this->never())->method('stopConsuming');

    $server->processMessage($message);
});
