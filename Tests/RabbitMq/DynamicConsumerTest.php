<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\DynamicConsumer;

class DynamicConsumerTest extends ConsumerTest
{   
    public function getConsumer($amqpConnection, $amqpChannel)
    {
        return new DynamicConsumer($amqpConnection, $amqpChannel);
    }
    
    /**
     * Preparing QueueOptionsProviderInterface instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueOptionsProviderInterface
     */
    private function prepareQueueOptionsProvider()
    {
        return $this->getMockBuilder('\OldSound\RabbitMqBundle\Provider\QueueOptionsProviderInterface')
            ->getMock();
    }
    
    public function testQueueOptionsPrivider()
    {
        $amqpConnection = $this->prepareAMQPConnection();
        $amqpChannel = $this->prepareAMQPChannel();
        $consumer = $this->getConsumer($amqpConnection, $amqpChannel);
        $consumer->setContext('foo');
        
        $queueOptionsProvider = $this->prepareQueueOptionsProvider();
        $queueOptionsProvider->expects($this->once())
            ->method('getQueueOptions')
            ->will($this->returnValue(
                array(
                    'name' => 'queue_foo',
                    'routing_keys' => array(
                        'foo.*'
                    )
                )
            ));
        
        $consumer->setQueueOptionsProvider($queueOptionsProvider);
        
        $reflectionClass = new \ReflectionClass(get_class($consumer));
        $reflectionMethod = $reflectionClass->getMethod('mergeQueueOptions');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($consumer);        
    }
}
