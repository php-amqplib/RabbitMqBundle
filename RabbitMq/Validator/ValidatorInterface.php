<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Validator;

interface ValidatorInterface
{
    public function setSchema($schema, $additionalProperties = array());
    public function validate(string $msg);
    public function getContentType();
}
