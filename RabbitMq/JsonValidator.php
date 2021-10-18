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
    public $schema_url = null;

    public function setSchema($schema, $schema_url=null, $definitions=null) {
        $this->definitions = $definitions;
        $this->schema = $schema;
        $this->schema_url = $schema_url;
    }


    public function validate($msg)
    {
        try{
            $options = new Context();

            if ($this->definitions != null) {
                $refProvider = new Preloaded();
                $refProvider->setSchemaData($this->schema_url, json_decode(file_get_contents($this->definitions, true)));
                $options->remoteRefProvider = $refProvider;
            }

            $schema = Schema::import(json_decode(file_get_contents($this->schema, true)), $options);
            $schema->in(json_decode($msg));
            return null;
        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    public function getContentType() {
        return "application/json";
    }
}
