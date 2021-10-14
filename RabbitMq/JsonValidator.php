<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Exception;
use Swaggest\JsonSchema\RemoteRef\Preloaded;

class JsonValidator implements ValidatorInterface
{
    public $definitions = null;
    public $schema = null;

    public function setSchema($schema, $definitions=null) {
        $this->definitions = $definitions;
        $this->schema = $schema;
    }


    public function isValid($msg)
    {
        try{
            $options = new Context();

            if ($this->definitions != null) {
                $refProvider = new Preloaded();
                $refProvider->setSchemaData("defs.schema", json_decode(file_get_contents("./defs.schema", true)));
                $options->remoteRefProvider = $refProvider;
            }

            $schema = Schema::import(json_decode(file_get_contents($this->schema, true)), $options);
            $schema->in($msg);
            return null;
        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    public function getContentType() {
        return "application/json";
    }
}