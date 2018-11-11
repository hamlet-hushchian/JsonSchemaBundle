<?php

namespace Hamlet\JsonSchemaBundle\Model\Config;


use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\Variable;
use Hamlet\JsonSchemaBundle\Model\Factory\PropertyFactory;

class ArrayConfig implements IConfig
{
    /** @var ObjectProperty */
    protected $rootProperty;

    /** @var Variable[] */
    protected $variables;

    /** @var array */
    protected $customData;

    /** @var array */
    protected $if;

    /** @var array */
    protected $then;

    /** @var array */
    protected $oneOf;

    /** @var array */
    protected $allOf;


    /**
     * @param array $array
     *
     * @throws BuildException
     */
    public function __construct($array)
    {
        $this->rootProperty = PropertyFactory::get()->createFromArray($array);
        if (!empty($array['variables'])) {
            $this->addVariables($array['variables']);
        }
        if (!empty($array['customData'])) {
            $this->addCustomData($array['customData']);
        }
        if (!empty($array['if'])) {
            $this->addIf($array['if']);
        }
        if (!empty($array['then'])) {
            $this->addThen($array['then']);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function addVariables(array $variables)
    {
        if (empty($this->variables)) {
            $this->variables = [];
        }
        foreach ($variables as $name => $variable) {
            $this->addVariable(new Variable($name, $variable));
        }

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function addVariable(Variable $variable)
    {
        $this->variables[$variable->getName()] = $variable;

        return $this;
    }


    /**
     * @return ObjectProperty
     */
    public function getRootProperty()
    {
        return $this->rootProperty;
    }


    /**
     * @return Variable[]
     */
    public function getVariables()
    {
        return $this->variables;
    }


    /**
     * @param array $customData
     *
     * @return $this
     */
    public function addCustomData(array $customData)
    {
        $this->customData = $customData;

        return $this;
    }


    /**
     * @return array
     */
    public function getCustomData()
    {
        return $this->customData;
    }


    /**
     * @param array $if
     *
     * @return ArrayConfig
     */
    public function addIf(array $if)
    {
        $this->if = $if;

        return $this;
    }


    /**
     * @return array
     */
    public function getIf()
    {
        return $this->if;
    }


    /**
     * @param array $then
     *
     * @return ArrayConfig
     */
    public function addThen(array $then)
    {
        $this->then = $then;

        return $this;
    }


    /**
     * @return array
     */
    public function getThen()
    {
        return $this->then;
    }


    /**
     * @param array $oneOf
     *
     * @return ArrayConfig
     */
    public function addOneOf(array $oneOf)
    {
        $this->oneOf = $oneOf;

        return $this;
    }


    /**
     * @return array
     */
    public function getOneOf()
    {
        return $this->oneOf;
    }


    /**
     * @param array $allOf
     *
     * @return ArrayConfig
     */
    public function addAllOf(array $allOf)
    {
        $this->allOf = $allOf;

        return $this;
    }


    /**
     * @return array
     */
    public function getAllOf()
    {
        return $this->allOf;
    }
}