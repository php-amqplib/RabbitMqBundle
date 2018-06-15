<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class RpcClientTest extends TestCase
{
    public function testProcessMessageWithCustomUnserializer()
    {
        /** @var RpcClient $client */
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(array('sendReply', 'maybeStopConsumer'))
            ->disableOriginalConstructor()
            ->getMock();
        /** @var AMQPMessage $message */
        $message = $this->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
            ->setMethods(array('get'))
            ->setConstructorArgs(array('message'))
            ->getMock();
        $serializer = $this->getMockBuilder('\Symfony\Component\Serializer\SerializerInterface')
            ->setMethods(array('serialize', 'deserialize'))
            ->getMock();
        $serializer->expects($this->once())->method('deserialize')->with('message', 'json', null);
        $client->initClient(true);
        $client->setUnserializer(function($data) use ($serializer) {
            $serializer->deserialize($data, 'json', null);
        });
        $client->processMessage($message);
    }

    public function testProcessMessageWithNotifyMethod()
    {
        /** @var RpcClient $client */
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(array('sendReply', 'maybeStopConsumer'))
            ->disableOriginalConstructor()
            ->getMock();
        $expectedNotify = 'message';
        /** @var AMQPMessage $message */
        $message = $this->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
            ->setMethods(array('get'))
            ->setConstructorArgs(array($expectedNotify))
            ->getMock();
        $notified = false;
        $client->notify(function ($message) use (&$notified) {
            $notified = $message;
        });

        $client->initClient(false);
        $client->processMessage($message);

        $this->assertSame($expectedNotify, $notified);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidParameterOnNotify()
    {
        /** @var RpcClient $client */
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(array('sendReply', 'maybeStopConsumer'))
            ->disableOriginalConstructor()
            ->getMock();

        $client->notify('not a callable');
    }
}
