<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\Validators\EmailValidator;
use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class EmailStringProperty extends StringProperty
{

    /** @var string */
    protected $format = 'email';


    /**
     * {@inheritdoc}
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $errors = parent::validate($input, $connector);

        try {
            EmailValidator::validate($input);
        } catch (\Exception $e) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::NOT_VALID_EMAIL,
                "Value '{$input}' is not a valid email"
            );
        }

        return $errors;
    }
}