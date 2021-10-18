<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ValidatorInterface
{
    public function setSchema($schema, $definitions=null);
    public function validate(string $msg);
    public function getContentType();
}
