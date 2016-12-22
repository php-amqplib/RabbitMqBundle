<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;

class ProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testReconnect()
    {
        $connection = $this->prophesize('\PhpAmqpLib\Connection\AMQPLazyConnection');
        $channel = $this->prophesize('\PhpAmqpLib\Channel\AMQPChannel');
        $producer = new Producer($connection->reveal());

        $connection->isConnected()->willReturn(true);
        $connection->reconnect()->shouldBeCalled();
        $connection->close()->shouldBeCalled();
        $connection->channel()->willReturn($channel->reveal());

        $producer->reconnect();
        $producer->getChannel();
    }

    public function testEnableConfirmationWhenChannelIsNotSet()
    {
        $connection = $this->prophesize('\PhpAmqpLib\Connection\AMQPLazyConnection');
        $channel = $this->prophesize('\PhpAmqpLib\Channel\AMQPChannel');
        $producer = new Producer($connection->reveal());

        $channel->close()->shouldBeCalled();
        $channel->confirm_select()->shouldBeCalled();

        $producer->enableConfirmation();
        $producer->setChannel($channel->reveal());
    }

    public function testEnableConfirmationWhenChannelIsSet()
    {
        $connection = $this->prophesize('\PhpAmqpLib\Connection\AMQPLazyConnection');
        $channel = $this->prophesize('\PhpAmqpLib\Channel\AMQPChannel');
        $producer = new Producer($connection->reveal());

        $channel->close()->shouldBeCalled();
        $channel->confirm_select()->shouldBeCalled();

        $producer->setChannel($channel->reveal());
        $producer->enableConfirmation();
    }

    public function testWaitConfirmation()
    {
        $connection = $this->prophesize('\PhpAmqpLib\Connection\AMQPLazyConnection');
        $channel = $this->prophesize('\PhpAmqpLib\Channel\AMQPChannel');

        $producer = new Producer($connection->reveal());

        $channel->close()->shouldBeCalled();
        $channel->confirm_select()->shouldBeCalled();
        $channel->getChannelId()->willReturn('channel_id');
        $channel->wait_for_pending_acks($producer->getWaitConfirmationTimeout())->shouldBeCalled();

        $producer->setChannel($channel->reveal());
        $producer->enableConfirmation();
        $producer->waitConfirmation();
    }

    public function testSetWaitConfirmationTimeout()
    {
        $connection = $this->prophesize('\PhpAmqpLib\Connection\AMQPLazyConnection');

        $producer = new Producer($connection->reveal());
        $producer->setWaitConfirmationTimeout(5);
        $this->assertEquals(5, $producer->getWaitConfirmationTimeout());
    }
}