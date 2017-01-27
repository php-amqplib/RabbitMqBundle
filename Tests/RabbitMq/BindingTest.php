<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;


use OldSound\RabbitMqBundle\RabbitMq\Binding;

class BindingTest extends \PHPUnit_Framework_TestCase
{

    protected function getBinding($amqpConnection, $amqpChannel)
    {
        return new Binding($amqpConnection, $amqpChannel);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareAMQPConnection()
    {
        return $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function prepareAMQPChannel($channelId = null)
    {
        $channelMock = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock->expects($this->any())
            ->method('getChannelId')
            ->willReturn($channelId);
        return $channelMock;
    }

    public function testQueueBind()
    {
        $ch = $this->prepareAMQPChannel('channel_id');
        $con = $this->prepareAMQPConnection();

        $source = 'example_source';
        $destination = 'example_destination';
        $key = 'example_key';
        $ch->expects($this->once())
            ->method('queue_bind')
            ->will($this->returnCallback(function ($d, $s, $k, $n, $a) use ($destination, $source, $key) {
                \PHPUnit_Framework_Assert::assertSame($destination, $d);
                \PHPUnit_Framework_Assert::assertSame($source, $s);
                \PHPUnit_Framework_Assert::assertSame($key, $k);
                \PHPUnit_Framework_Assert::assertFalse($n);
                \PHPUnit_Framework_Assert::assertNull($a);
            }));

        $binding = $this->getBinding($con, $ch);
        $binding->setExchange($source);
        $binding->setDestination($destination);
        $binding->setRoutingKey($key);
        $binding->setupFabric();
    }

    public function testExhangeBind()
    {
        $ch = $this->prepareAMQPChannel('channel_id');
        $con = $this->prepareAMQPConnection();

        $source = 'example_source';
        $destination = 'example_destination';
        $key = 'example_key';
        $ch->expects($this->once())
            ->method('exchange_bind')
            ->will($this->returnCallback(function ($d, $s, $k, $n, $a) use ($destination, $source, $key) {
                \PHPUnit_Framework_Assert::assertSame($destination, $d);
                \PHPUnit_Framework_Assert::assertSame($source, $s);
                \PHPUnit_Framework_Assert::assertSame($key, $k);
                \PHPUnit_Framework_Assert::assertFalse($n);
                \PHPUnit_Framework_Assert::assertNull($a);
            }));

        $binding = $this->getBinding($con, $ch);
        $binding->setExchange($source);
        $binding->setDestination($destination);
        $binding->setRoutingKey($key);
        $binding->setDestinationIsExchange(true);
        $binding->setupFabric();
    }
}
