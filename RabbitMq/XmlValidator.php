<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use DOMDocument;

class XmlValidator implements ValidatorInterface
{
    public $schema = null;

    public function setSchema($schema, $schema_url=null, $definitions=null) {
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
