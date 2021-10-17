<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ValidatorInterface
{
    public function setSchema($schema, $definitions=null);
    public function isValid(string $msg);
    public function getContentType();
}
