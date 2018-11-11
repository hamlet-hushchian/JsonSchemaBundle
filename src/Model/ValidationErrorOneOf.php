<?php

namespace Hamlet\JsonSchemaBundle\Model;

class ValidationErrorOneOf
{
    /**
     * @var array $caseErrors
     */
    private $caseErrors = [];

    /**
     * @var SchemaValidationError | bool
     */
    private $errorTitle = false;

    /**
     * @param array $caseErrors
     * @param SchemaValidationError | bool
     */
    public function __construct(array $caseErrors = [], $errorTitle)
    {
        foreach ($caseErrors as $errors) {
            foreach ($errors as $error) {
                if (!($error instanceof SchemaValidationError)) {
                    throw new \LogicException('Wrong error type');
                }
            }
        }
        if($errorTitle && !($errorTitle instanceof SchemaValidationError))
            throw new \LogicException('Wrong error type fo error title');
        $this->caseErrors = $caseErrors;
        $this->errorTitle = $errorTitle;
    }

    /**
     * @return array
     */
    public function render()
    {
        $errorsOneOf = [];
        foreach ($this->caseErrors as $case => $errors) {
            /** @var SchemaValidationError $error */
            foreach ($errors as $error)
            {
                $errorsOneOf[] = new SchemaValidationError(
                    $error->getPath(),
                    $error->getCode(),
                    $error->getMessage() . " according to case $case in OneOf validation"
                );
            }
        }
        if($this->errorTitle)
            array_unshift($errorsOneOf,$this->errorTitle);
        return $errorsOneOf;
    }
}