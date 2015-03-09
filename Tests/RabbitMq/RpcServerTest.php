<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\RpcServer;
use PhpAmqpLib\Message\AMQPMessage;

class RpcServerTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessMessageWithCustomSerializer()
    {
        /** @var RpcServer $server */
        $server = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcServer')
            ->setMethods(array('sendReply', 'maybeStopConsumer'))
            ->disableOriginalConstructor()
            ->getMock();
        $message = $this->getMock('\PhpAmqpLib\Message\AMQPMessage', array('get'));
        $message->delivery_info = array(
            'channel' => $this->getMock('\PhpAmqpLib\Channel\AMQPChannel', array(), array(), '', false),
            'delivery_tag' => null
        );
        $server->setCallback(function() {
            return 'message';
        });
        $serializer = $this->getMock('\Symfony\Component\Serializer\SerializerInterface', array('serialize', 'deserialize'));
        $serializer->expects($this->once())->method('serialize')->with('message', 'json');
        $server->setSerializer(function($data) use ($serializer) {
            $serializer->serialize($data, 'json');
        });
        $server->processMessage($message);
    }
}
