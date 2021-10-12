<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Exception;


class JsonValidator implements ValidatorInterface
{
    public function isValid($msg, $validatorFile)
    {
        try{
            $schema = Schema::import(json_decode(file_get_contents($validatorFile, true)));
            $schema->in($msg);
            return true;
        }catch (Exception $e){
            return false;
        }
    }
}