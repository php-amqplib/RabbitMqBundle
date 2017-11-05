<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\PublisherConfirmsProducer;
use PhpAmqpLib\Connection\AbstractConnection;

/**
 * @coversDefaultClass \OldSound\RabbitMqBundle\RabbitMq\Producer
 */
final class PublisherConfirmsProducerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::publish
     */
    public function testPublish()
    {
        $channelStub = self::getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $channelStub
            ->expects(self::once())
            ->method('confirm_select')
            ->with()
        ;

        $channelStub
            ->expects(self::once())
            ->method('wait_for_pending_acks_returns')
            ->with(42)
        ;

        $connectionStub = self::getMockBuilder('\PhpAmqpLib\Connection\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $connectionStub
            ->method('channel')
            ->willReturn($channelStub)
        ;

        /* @var $connectionStub AbstractConnection */

        $producer = new PublisherConfirmsProducer($connectionStub);

        $producer->setPublisherConfirmsTimeout(42);

        $producer->setExchangeOptions(
            array(
                'name' => 'foo',
                'type' => 'foo'
            )
        );

        $producer->publish('test');

        self::assertSame(
            42,
            $producer->getPublisherConfirmsTimeout()
        );
    }

    /**
     * @covers ::publish
     */
    public function testPublishMultiplePublishCallsOnlyCallConfirmSelectOnce()
    {
        $channelStub = self::getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $channelStub
            ->expects(self::once())
            ->method('confirm_select')
            ->with()
        ;

        $channelStub
            ->expects(self::exactly(2))
            ->method('wait_for_pending_acks_returns')
            ->with(42)
        ;

        $connectionStub = self::getMockBuilder('\PhpAmqpLib\Connection\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $connectionStub
            ->method('channel')
            ->willReturn($channelStub)
        ;

        /* @var $connectionStub AbstractConnection */

        $producer = new PublisherConfirmsProducer($connectionStub);

        $producer->setPublisherConfirmsTimeout(42);

        $producer->setExchangeOptions(
            array(
                'name' => 'foo',
                'type' => 'foo'
            )
        );

        $producer->publish('test');
        $producer->publish('test');

        self::assertSame(
            42,
            $producer->getPublisherConfirmsTimeout()
        );
    }
}
