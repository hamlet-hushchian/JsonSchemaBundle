<?php

namespace Hamlet\JsonSchemaBundle\Model;


use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;

interface ISchemaConnector
{
    /**
     * @param string $url
     * @param string $method
     * @param array $fields
     *
     * @return SchemaValidationError[]
     */
    public function validate($url, $method, $fields);

    /**
     * @param string $url
     * @param string $method
     * @param array $fields
     *
     * @return ObjectProperty
     * @throws \Exception
     */
    public function getSchema($url, $method, $fields);
}