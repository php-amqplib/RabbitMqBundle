<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Validator;

use DOMDocument;

class XmlValidator implements ValidatorInterface
{
    private $schema = null;

    public function setSchema($schema, $additionalProperties = array()) {
        $this->schema = $schema;
    }

    public function validate($msg)
    {
        $xml = new DOMDocument();
        $xml->load($msg);
        return $xml->schemaValidate($this->schema) == true ? null : "XML schema validation failed.";
    }

    public function getContentType() {
        return "application/xml";
    }
}
