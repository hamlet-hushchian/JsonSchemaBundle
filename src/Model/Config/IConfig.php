<?php

namespace Hamlet\JsonSchemaBundle\Model\Config;


use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\Property;
use Hamlet\JsonSchemaBundle\Model\Elements\Variable;

interface IConfig
{
    /**
     * @param Variable[] $variables
     *
     * @return self
     */
    public function addVariables(array $variables);

    /**
     * @param Variable $variable
     *
     * @return self
     */
    public function addVariable(Variable $variable);

    /**
     * @return ObjectProperty
     */
    public function getRootProperty();

    /**
     * @return Variable[]
     */
    public function getVariables();

    /**
     * @return array
     */
    public function getCustomData();

    /**
     * @return array
     */
    public function getIf();

    /**
     * @return array
     */
    public function getThen();

    /**
     * @return array
     */
    public function getOneOf();

    /**
     * @return array
     */
    public function getAllOf();
}
