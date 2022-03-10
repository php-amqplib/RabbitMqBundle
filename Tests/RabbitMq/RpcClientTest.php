<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use InvalidArgumentException;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class RpcClientTest extends TestCase
{
    public function testProcessMessageWithCustomUnserializer()
    {
        /** @var RpcClient $client */
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(['sendReply', 'maybeStopConsumer'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var AMQPMessage $message */
        $message = $this->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
            ->setMethods(['get'])
            ->setConstructorArgs(['message'])
            ->getMock();
        $serializer = $this->getMockBuilder('\Symfony\Component\Serializer\SerializerInterface')
            ->setMethods(['serialize', 'deserialize'])
            ->getMock();
        $serializer->expects($this->once())->method('deserialize')->with('message', 'json', null);
        $client->initClient(true);
        $client->setUnserializer(function ($data) use ($serializer) {
            $serializer->deserialize($data, 'json', '');
        });
        $client->processMessage($message);
    }

    public function testProcessMessageWithNotifyMethod()
    {
        /** @var RpcClient $client */
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(['sendReply', 'maybeStopConsumer'])
            ->disableOriginalConstructor()
            ->getMock();
        $expectedNotify = 'message';
        /** @var AMQPMessage $message */
        $message = $this->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
            ->setMethods(['get'])
            ->setConstructorArgs([$expectedNotify])
            ->getMock();
        $notified = false;
        $client->notify(function ($message) use (&$notified) {
            $notified = $message;
        });

        $client->initClient(false);
        $client->processMessage($message);

        $this->assertSame($expectedNotify, $notified);
    }

    public function testInvalidParameterOnNotify()
    {
        /** @var RpcClient $client */
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(['sendReply', 'maybeStopConsumer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(InvalidArgumentException::class);

        $client->notify('not a callable');
    }

    public function testChannelCancelOnGetRepliesException()
    {
        $client = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\RpcClient')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $channel = $this->createMock('\PhpAmqpLib\Channel\AMQPChannel');
        $channel->expects($this->any())
            ->method('getChannelId')
            ->willReturn('test');
        $channel->expects($this->once())
            ->method('wait')
            ->willThrowException(new AMQPTimeoutException());

        $this->expectException(AMQPTimeoutException::class);

        $channel->expects($this->once())
            ->method('basic_cancel');

        $client->setChannel($channel);
        $client->addRequest('a', 'b', 'c');

        $client->getReplies();
    }
}
