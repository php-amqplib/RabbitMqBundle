<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ValidatorInterface
{
    public function setSchema($schema, $schema_url=null, $definitions=null);
    public function validate(string $msg);
    public function getContentType();
}
