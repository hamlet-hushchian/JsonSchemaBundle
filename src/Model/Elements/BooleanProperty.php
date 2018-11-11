<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class BooleanProperty extends Property
{

    /** @var string */
    protected $type = 'boolean';

    /**
     * @param Property $anotherProperty
     *
     * @return Property
     */
    public function merge(Property $anotherProperty)
    {
        return parent::merge($anotherProperty);
    }


    /**
     * {@inheritdoc}
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $errors = parent::validate($input, $connector);

        if (!is_bool($input)) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::NOT_BOOLEAN,
                "Input value is not boolean: {$input}"
            );
        }

        return $errors;
    }
}