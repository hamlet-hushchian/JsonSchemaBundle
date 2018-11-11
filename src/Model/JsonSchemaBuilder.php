<?php

namespace Hamlet\JsonSchemaBundle\Model;


use Hamlet\JsonSchemaBundle\Model\Config\IConfig;
use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\Property;
use Hamlet\JsonSchemaBundle\Model\Elements\Variable;


class JsonSchemaBuilder
{
    /** @var ObjectProperty */
    protected $rootProperty;

    /** @var Variable[] */
    protected $variables = [];

    /** @var array */
    protected $customData = [];

    /** @var string */
    private $id;

    /** @var array */
    private $oneOf = [];

    /** @var array */
    private $allOf = [];


    /**
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->id = $id;
    }

    /**
     * @param IConfig $config
     *
     * @return $this
     */
    public function addConfig(IConfig $config)
    {
        if (!empty($config->getRootProperty())) {
            $this->addRoot($config->getRootProperty());
        }
        if (!empty($config->getVariables())) {
            $this->addVariables($config->getVariables());
        }
        if (!empty($config->getCustomData())) {
            $this->addCustomData($config->getCustomData());
        }
        if (!empty($config->getOneOf())) {
            $this->addOneOf($config->getOneOf());
        }
        if (!empty($config->getAllOf())) {
            $this->addAllOf($config->getAllOf());
        }

        return $this;
    }


    /**
     * @return ObjectProperty
     */
    public function getRoot()
    {
        return $this->rootProperty;
    }

    /**
     * @param ObjectProperty $root
     *
     * @return $this
     */
    public function addRoot(ObjectProperty $root)
    {
        if (empty($this->rootProperty)) {
            $this->rootProperty = $root;
        } else {
            $this->rootProperty = $this->rootProperty->merge($root);
        }

        return $this;
    }

    /**
     * @param array $variables
     *
     * @return $this
     */
    public function addVariables(array $variables)
    {
        $this->variables = array_merge($this->variables, $variables);

        return $this;
    }

    /**
     * @param array $customData
     *
     * @return $this
     */
    public function addCustomData(array $customData)
    {
        $this->customData = array_replace_recursive($this->customData, $customData);

        return $this;
    }

    /**
     * @param bool $public
     *
     * @return array
     * @throws BuildException
     */
    public function generate($public = true)
    {
        $rootProperties = $this->rootProperty->display($public);

        if (!empty($rootProperties['definitions']) && $public) {
            $rootProperties = $this->processInlineDefinitions($rootProperties);
        }


        if ($public) {
            if(isset($rootProperties['properties']))
                $rootProperties['properties'] = $this->fillVariables($rootProperties['properties']);
        } else {
            $rootProperties['variables'] = $this->getPlainVariables();
        }

        if (!empty($this->customData)) {
            $rootProperties['customData'] = $this->customData;
        }

        if (!empty($this->oneOf)) {
            $rootProperties['oneOf'] = $this->oneOf;
        }

        if (!empty($this->allOf)) {
            $rootProperties['allOf'] = $this->allOf;
        }


        return array_filter($rootProperties);
    }

    /**
     * @return array
     */
    protected function getPlainVariables()
    {
        $variables = [];
        foreach ($this->variables as $variable) {
            $variables[$variable->getName()] = $variable->getValue();
        }

        return $variables;
    }

    /**
     * @param array $properties
     *
     * @return array
     */
    protected function fillVariables($properties)
    {
        array_walk_recursive(
            $properties,
            function (&$item, $key, $variables) {
                $matches = [];
                if (preg_match('/{{(?P<variable>\w+)}}/', $item, $matches)) {
                    $variableName = $matches['variable'];
                    if (!isset($variables[$variableName])) {
                        throw new BuildException(
                            'Variable ' . $variableName . ' not set' . (!empty($this->id) ? ' in ' . $this->id : '')
                        );
                    }
                    /** @var Variable[] $variables */
                    $item = $variables[$variableName]->getValue();
                }
            },
            $this->variables
        );

        return $properties;
    }

    /**
     * @param $rootObject
     *
     * @return array
     * @throws BuildException
     */
    protected function processInlineDefinitions($rootObject)
    {
        $definitions = $rootObject['definitions'];
        $callable = function ($properties) use (&$callable, $definitions) {
            foreach ($properties as $id => $property) {
                foreach ([Property::NOT, Property::ONE_OF, Property::ALL_OF, Property::ANY_OF] as $containerType) {
                    if (!empty($property[$containerType])) {
                        foreach ($property[$containerType] as $number => $item) {
                            $processed = $callable([$item]);
                            $properties[$id][$containerType][$number] = array_shift($processed);
                        }
                    }
                }

                if ($property['type'] == 'object' && !empty($property['properties'])) {
                    $properties[$id]['properties'] = $callable($property['properties']);
                    continue;
                }
                if ($property['type'] != 'ref') {
                    continue;
                }

                if (empty($property['$ref'])) {
                    throw new BuildException($id . ' has no $ref field');
                }
                $referenceId = str_replace('#/definitions/', '', $property['$ref']);
                if (empty($definitions[$referenceId])) {
                    throw new BuildException($referenceId . ' reference not found');
                }
                if (!empty($definitions[$referenceId]['inline']) && $definitions[$referenceId]['inline']) {
                    foreach ([
                                 'default',
                                 'const',
                                 'required',
                                 'description',
                             ] as $item) { // todo: refactor it, i don't have any ideas how
                        if (!empty($property[$item])) {
                            unset($definitions[$referenceId][$item]);
                        }
                    }
                    $properties[$id] = array_merge($properties[$id], $definitions[$referenceId]);
                    if ($properties[$id]['type'] != 'ref') {
                        unset($properties[$id]['$ref']);
                    } else {
                        $properties[$id] = $callable([$id => $properties[$id]])[$id];
                    }

                    if ($properties[$id]['type'] == 'object' && !empty($properties[$id]['properties'])) {
                        $properties[$id]['properties'] = $callable($properties[$id]['properties']);
                    }
                    unset($properties[$id]['inline']);
                }
            }

            return $properties;
        };

        $rootObject['properties'] = $callable($rootObject['properties']);

        foreach ($definitions as $key => $definition) {
            if (!empty($definition['inline']) && $definition['inline']) {
                unset($definitions[$key]);
            }
        }

        $rootObject['definitions'] = $definitions;

        return $rootObject;
    }


    /**
     * @param array $oneOf
     *
     * @return JsonSchemaBuilder
     */
    private function addOneOf(array $oneOf)
    {
        $this->oneOf = array_merge($this->oneOf, $oneOf);

        return $this;
    }


    /**
     * @param array $allOf
     *
     * @return JsonSchemaBuilder
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