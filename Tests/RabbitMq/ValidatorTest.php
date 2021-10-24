<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use PHPUnit\Framework\TestCase;

class JsonSchemaTest extends TestCase
{
    public function testJsonValidatorFunction()
    {
        $jsonValidator = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\Validator\JsonValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $jsonValidator->setSchema(
            "OldSound\RabbitMqBundle\TestValidation/schema/JsonValidation.schema",
            null
        );

        $json_msg = <<<'JSON'
        {
            "firstName": "John",
            "lastName": "Doe",
            "age": 21
        }
JSON;
        $jsonValidator->method('getContentType')->willReturn('application/json');
        $this->assertEquals(null, $jsonValidator->validate($json_msg));
        
    }

    public function testXmlValidatorFunction()
    {
        $xmlValidator = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\Validator\XmlValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $xmlValidator->setSchema(
            "OldSound\RabbitMqBundle\TestValidation/schema/XmlValidation.xsd",
            null
        );

        $xml_msg = <<<'XML'
        <person>
        <firstName>John</firstName>
        <from>Doe</from>
        <age>21</age>
        </person>
XML;
        $xmlValidator->method('getContentType')->willReturn('application/xml');
        $this->assertEquals(null, $xmlValidator->validate($xml_msg));
    }

    public function testJsonValidatorWithSchemaDataFunction()
    {
        $jsonValidator = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\Validator\JsonValidator')
            ->disableOriginalConstructor()
            ->getMock();

            $jsonValidator->setSchema(
            "OldSound\RabbitMqBundle\TestValidation/schema/top_level.schema",
            array(
                'schema_url'=>"defs.schema",
                'definitions'=>"%kernel.project_dir%/config/jsonschema/common_objects.schema"
            )
        );

        $json_msg = <<<'JSON'
        {
            "prefix": "Mr",
            "firstName": "John",
            "lastName": "Doe",
            "age": 21,
            "language": "EN"
        }
JSON;
        $jsonValidator->method('getContentType')->willReturn('application/json');
        $this->assertEquals(null, $jsonValidator->validate($json_msg));
    }
}
