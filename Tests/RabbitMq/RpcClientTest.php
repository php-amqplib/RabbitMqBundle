<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\RpcClient;

class RpcClientTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessMessageWithCustomUnserializer()
    {
        /** @var RpcClient $client */
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(array('sendReply', 'maybeStopConsumer'))
            ->disableOriginalConstructor()
            ->getMock();
        $message = $this->getMock('\PhpAmqpLib\Message\AMQPMessage', array('get'), array('message'));
        $serializer = $this->getMock('\Symfony\Component\Serializer\SerializerInterface', array('serialize', 'deserialize'));
        $serializer->expects($this->once())->method('deserialize')->with('message', 'json', null);
        $client->initClient(true);
        $client->setUnserializer(function($data) use ($serializer) {
            $serializer->deserialize($data, 'json', null);
        });
        $client->processMessage($message);
    }
}
