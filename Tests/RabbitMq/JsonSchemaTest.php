<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;

class JsonSchemaTest extends TestCase
{
    protected function getConsumer($amqpConnection, $amqpChannel)
    {
        return new Consumer($amqpConnection, $amqpChannel);
    }

    protected function prepareAMQPConnection()
    {
        return $this->getMockBuilder(AMQPStreamConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function prepareAMQPChannel()
    {
        return $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidateJsonMessageFunction()
    {
        $producer = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\Producer')
            ->disableOriginalConstructor()
            ->getMock();

        
        $amqpConnection = $this->prepareAMQPConnection();
        $amqpChannel = $this->prepareAMQPChannel();
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        // disable autosetup fabric so we do not mock more objects
        $consumer->disableAutoSetupFabric();
        $consumer->setChannel($amqpChannel);
        $producer->setContentType("application/json");
        $json_msg = <<<'JSON'
        {
            "firstName": "John",
            "lastName": "Doe",
            "age": 21
        }
JSON;
    $this->assertEquals(null, $producer->publish($json_msg));
    }
}
