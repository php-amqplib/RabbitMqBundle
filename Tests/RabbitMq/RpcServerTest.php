<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\RpcServer;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class RpcServerTest extends TestCase
{
    public function testProcessMessageWithCustomSerializer()
    {
        /** @var RpcServer $server */
        $server = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcServer')
            ->setMethods(array('sendReply', 'maybeStopConsumer'))
            ->disableOriginalConstructor()
            ->getMock();
        $message = $this->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
            ->setMethods( array('get'))
            ->getMock();
        $message->setChannel(
            $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
                ->setMethods(array())->setConstructorArgs(array())
                ->setMockClassName('')
                ->disableOriginalConstructor()
                ->getMock()
        );
        $message->setDeliveryTag(0);
        $server->setCallback(function() {
            return 'message';
        });
        $serializer = $this->getMockBuilder('\Symfony\Component\Serializer\SerializerInterface')
            ->setMethods(array('serialize', 'deserialize'))
            ->getMock();
        $serializer->expects($this->once())->method('serialize')->with('message', 'json');
        $server->setSerializer(function($data) use ($serializer) {
            $serializer->serialize($data, 'json');
        });
        $server->processMessage($message);
    }
}
