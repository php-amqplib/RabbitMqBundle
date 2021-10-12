<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

interface ValidatorInterface
{
    public function isValid(string $msg, string $validatorFile);
}