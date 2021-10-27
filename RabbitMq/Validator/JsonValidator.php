<?php

namespace OldSound\RabbitMqBundle\RabbitMq\Validator;

use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Exception;
use Swaggest\JsonSchema\RemoteRef\Preloaded;

class JsonValidator implements ValidatorInterface
{
    private $definitions = null;
    private $schema = null;
    private $schema_url = null;

    public function setSchema($schema, $additionalProperties = array()) {
        $this->schema = $schema;
        if(isset($additionalProperties['definitions']) && isset($additionalProperties['schema_url'])){
            $this->definitions = $additionalProperties['definitions'];
            $this->schema_url = $additionalProperties['schema_url'];
        }
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
