<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use DOMDocument;

class XmlValidator implements ValidatorInterface
{
    public function isValid($msg, $validatorFile)
    {
        $xml = new DOMDocument();
        $xml->load($msg);
        return $xml->schemaValidate($validatorFile);

    }
}