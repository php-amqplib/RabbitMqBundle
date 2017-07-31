<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

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

    /**
     * @dataProvider provideTestSetWaitConfirmationTimeout
     */
    public function testSetWaitConfirmationTimeout($timeout, $expectedException)
    {
        $connection = $this->prophesize('\PhpAmqpLib\Connection\AMQPLazyConnection');

        $producer = new Producer($connection->reveal());

        try {
            $producer->setWaitConfirmationTimeout($timeout);
            $this->assertEquals($timeout, $producer->getWaitConfirmationTimeout());
        } catch (\InvalidArgumentException $e) {
            if ($expectedException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function provideTestSetWaitConfirmationTimeout()
    {
        return [
            'correct timeout' => [
                'timeout' => 5,
                'expectedException' => false,
            ],
            'timeout zero' => [
                'timeout' => 0,
                'expectedException' => false,
            ],
            'timeout less then zero' => [
                'timeout' => -1,
                'expectedException' => '\InvalidArgumentException',
            ],
            'timeout not integer' => [
                'timeout' => '5',
                'expectedException' => '\InvalidArgumentException',
            ],
        ];
    }
}
