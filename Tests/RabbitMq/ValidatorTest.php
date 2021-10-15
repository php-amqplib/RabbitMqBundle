<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use PHPUnit\Framework\TestCase;

class JsonSchemaTest extends TestCase
{
    public function testJsonValidatorFunction()
    {
        $jsonValidator = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\JsonValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $jsonValidator->setSchema(
            "OldSound\RabbitMqBundle\TestValidation/schema/JsonValidation.schema",
            null,
        );

        $json_msg = <<<'JSON'
        {
            "firstName": "John",
            "lastName": "Doe",
            "age": 21
        }
JSON;
        $jsonValidator->method('getContentType')->willReturn('application/json');
        $this->assertEquals(null, $jsonValidator->isValid($json_msg));
        
    }

    public function testXmlValidatorFunction()
    {
        $xmlValidator = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\XmlValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $xmlValidator->setSchema(
            "OldSound\RabbitMqBundle\TestValidation/schema/XmlValidation.xsd",
            null,
        );

        $xml_msg = <<<'XML'
        <xml>
        <firstName>John</firstName>
        <from>Doe</from>
        <age>21</age>
        </xml>
XML;
        $xmlValidator->method('getContentType')->willReturn('application/xml');
        $this->assertEquals(null, $xmlValidator->isValid($xml_msg));
    }
}
