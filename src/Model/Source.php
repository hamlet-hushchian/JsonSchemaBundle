<?php

namespace Hamlet\JsonSchemaBundle\Model;

use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;

class Source
{

    /** @var string */
    protected $url;

    /** @var string */
    protected $method;

    /** @var array */
    protected $fields = [];


    /**
     * @param string $url
     * @param string $method
     * @param array $fields
     */
    public function __construct($url, $method, $fields)
    {
        $this->url = $url;
        $this->method = $method;
        $this->fields = $fields;
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }


    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }


    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * @param string $field
     * @param string|array $value
     */
    public function setFieldValue($field, $value)
    {
        $this->fields[$field] = $value;
    }

    /**
     * @param ISchemaConnector $connector
     *
     * @return ObjectProperty
     */
    public function getSchema(ISchemaConnector $connector)
    {
        try {
            return $connector->getSchema($this->url, $this->method, $this->fields);
        } catch (\Exception $e) {
            return new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        }

    }


    /**
     * @param array
     *
     * @return Source
     * @throws BuildException
     */
    public static function fromArray($fields)
    {
        self::ensure(is_array($fields), 'source is not an array');
        self::ensure(array_key_exists('url', $fields), "'url' fields is missing in source");
        self::ensure(array_key_exists('method', $fields), "'method' fields is missing in source");
        self::ensure(array_key_exists('fields', $fields), "'fields' fields is missing in source");

        return new self(
            $fields['url'],
            $fields['method'],
            $fields['fields']
        );
    }


    /**
     * @return array
     */
    public function display()
    {
        return [
            'url'    => $this->getUrl(),
            'method' => $this->getMethod(),
            'fields' => $this->getFields(),
        ];
    }


    /**
     * @param bool $statement
     * @param string $errorMessage
     *
     * @throws BuildException
     */
    private static function ensure($statement, $errorMessage)
    {
        if (!$statement) {
            throw new BuildException($errorMessage);
        }
    }
}