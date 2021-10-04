<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Connection\AbstractConnection;
use PHPUnit\Framework\TestCase;

class JsonSchemaTest extends TestCase
{
    public function testValidateJsonMessageFunction(){

        // $msg = array('user_id' => 1235, 'image_path' => 'pic.png');
        // // $this->get('old_sound_rabbit_mq.upload_picture_producer')->publish(serialize($msg));
        // // $container = $kernel->getContainer();
        
        // $clinet = $this->get
        // $client = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\Producer');
        // //$con = new AbstractConnection("user", "pass");
        // dd($client);
        // $producer = new Producer($con);
        // $producer ->publish('abc');

    }
}