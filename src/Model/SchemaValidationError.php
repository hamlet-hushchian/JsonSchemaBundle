<?php

namespace Hamlet\JsonSchemaBundle\Model;


class SchemaValidationError
{

    /* String property errors */
    const MORE_THAN_MAX_LENGTH = 'schema.validation.max_length';

    const LESS_THAN_MIN_LENGTH = 'schema.validation.min_length';

    const REGEXP_IS_NOT_MATCH = 'schema.validation.pattern';

    /* EmailString property errors */
    const NOT_VALID_EMAIL = 'schema.validation.email_not_valid';

    /* DateString property errors */
    const NOT_VALID_DATE = 'schema.validation.date';

    const LESS_THAN_MIN_VALUE = 'schema.validation.min_value';

    const MORE_THAN_MAX_VALUE = 'schema.validation.max_value';

    /* Enum property errors */
    const NOT_IN_OPTIONS = 'schema.validation.enum';

    /* Boolean property errors */
    const NOT_BOOLEAN = 'schema.validation.boolean';

    /* Base property errors */
    const NOT_EQUALS_TO_CONST = 'schema.validation.readonly';

    const REQUIRED_PROPERTY_MISSING = 'schema.validation.required';

    const DEPEND_PROPERTY_EMPTY = 'schema.validation.dependency';

    /** Container property errors */
    const NOT_PASSED_ANY_CONTAINER_VALIDATION = 'schema.validation.container.not_passed';

    /** OneOf container property errors */
    const SATISFY_MULTIPLE_CASES_ONE_OF = 'schema.validation.container.one_of.satisfy_multiple';

    /* Internal errors */
    const DEFINITION_DOES_NOT_EXIST = 'schema.build.definition_does_not_exist';

    const CUSTOM_ERROR = 'schema.validation.custom';

    const THEN_CONDITION_NOT_PASSED = 'then_condition_not_passed';

    /** @var string */
    protected $path;

    /** @var string */
    protected $code;

    /** @var string */
    protected $message;


    /**
     * @param string $path
     * @param string $code
     * @param string $message
     */
    public function __construct($path, $code, $message = '')
    {
        $this->path = $path;
        $this->code = $code;
        $this->message = $message;
    }


    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }


    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }


    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }


    /**
     * @param bool $webSafe
     *
     * @return string
     */
    public function getMessage($webSafe = false)
    {
        return $webSafe ? htmlspecialchars($this->message) : $this->message;
    }


    /**
     * @return array
     */
    public function display()
    {
        return [
            'code'    => $this->getCode(),
            'message' => $this->getMessage(true),
            'path'    => $this->getPath(),
        ];
    }
}