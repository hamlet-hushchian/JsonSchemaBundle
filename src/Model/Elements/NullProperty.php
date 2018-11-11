<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class NullProperty extends Property
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $type = 'null';


    /**
     * {@inheritdoc}
     */
    public function display($public = true)
    {
        return [];
    }


    /**
     * {@inheritdoc}
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $errors = parent::validate($input, $connector);

        return $errors;
    }
}