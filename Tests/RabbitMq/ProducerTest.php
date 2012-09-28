<?php
namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * Tests the Producer class
 */
class ProducerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Set up method for the test
     */
    public function setUp()
    {
        $this->channelMock = $this->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()->setMethods(array('basic_publish', 'exchange_declare'))
            ->getMock();

        $this->connectionMock = $this->getMockBuilder('PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests the publish method with wrong properties
     * @expectedException PHPUnit_Framework_Error
     * @dataProvider testProperties
     */
    public function testPublishWithWrongProperties($msgBody, $routingKey, $additionalParameters)
    {
        $this->channelMock
            ->expects($this->never())
            ->method('basic_publish');

        $this->channelMock
            ->expects($this->once())
            ->method('exchange_declare');

        $producer = new Producer($this->connectionMock, $this->channelMock);
        $producer->setExchangeOptions(array('name'=>'test', 'type'=>'test'));
        $producer->publish($msgBody, $routingKey, $additionalParameters);
    }

    /**
     * Tests the publish method
     */
    public function testPublish()
    {
        $this->channelMock
            ->expects($this->once())
            ->method('basic_publish');

        $this->channelMock
            ->expects($this->once())
            ->method('exchange_declare');

        $producer = new Producer($this->connectionMock, $this->channelMock);
        $producer->setExchangeOptions(array('name'=>'test', 'type'=>'test'));
        $producer->publish('test');
    }

    /**
     * Data provider for the publish method
     * @return array
     */
    public function testProperties()
    {
        return array(
            array('test', 'test', 'test' ),
        );
    }

}
