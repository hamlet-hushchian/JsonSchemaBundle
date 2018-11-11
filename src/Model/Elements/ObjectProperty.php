<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;

use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class ObjectProperty extends Property
{
    const ROOT_PROPERTY_NAME = '#';


    protected $type = 'object';


    /** @var Property[] */
    protected $properties = [];

    /** @var Property[] */
    protected $definitions = [];

    /** @var ObjectProperty */
    protected $if;

    /** @var ObjectProperty */
    protected $then;

    /** @var ObjectProperty[] */
    protected $oneOf;

    /** @var ObjectProperty[] */
    protected $allOf;


    /**
     * @param Property $property
     *
     * @return $this
     */
    public function addProperty(Property $property)
    {
        $property->setParent($this);
        $this->properties[$property->getId()] = $property;

        return $this;
    }

    /**
     * @return Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }


    /**
     * @param string $id
     *
     * @return Property
     * @throws BuildException
     */
    public function getPropertyById($id)
    {
        $subPath = explode(self::PROPERTY_SEPARATOR, $id);
        $currentId = array_shift($subPath);

        if ($currentId == $this->getId()) {
            $currentId = array_shift($subPath);
        }

        if (!isset($this->properties[$currentId])) {
            throw new BuildException("Property '{$id}' not found in '{$this->getId()}' objectProperty.");
        }
        if (empty($subPath)) {
            return $this->properties[$currentId];
        }
        if (!($this->properties[$currentId] instanceof self)) {
            throw new BuildException(
                "Nested property '{$this->properties[$currentId]->getId()}' is not an objectProperty."
            );
        }

        return $this->properties[$currentId]->getPropertyById(implode(self::PROPERTY_SEPARATOR, $subPath));
    }


    /**
     * @param Property $definition
     *
     * @return $this
     */
    public function addDefinition(Property $definition)
    {
        $definition->setParent($this);
        $this->definitions[$definition->getId()] = $definition;

        return $this;
    }

    /**
     * @return Property[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param ObjectProperty $schema
     *
     * @return $this
     */
    public function addIf(ObjectProperty $schema)
    {
        $this->if = $schema;

        return $this;
    }

    /**
     * @return ObjectProperty
     */
    public function getIf()
    {
        return $this->if;
    }

    /**
     * @param ObjectProperty $schema
     *
     * @return $this
     */
    public function addThen(ObjectProperty $schema)
    {
        $this->then = $schema;

        return $this;
    }

    /**
     * @return ObjectProperty
     */
    public function getThen()
    {
        return $this->then;
    }


    /**
     * @param array $oneOf
     *
     * @return ObjectProperty
     * @throws BuildException
     */
    public function addOneOf(array $oneOf)
    {
        foreach ($oneOf as $item) {
            $this->oneOf[] = (new self())->fromArray($item);
        }

        return $this;
    }


    /**
     * @return ObjectProperty[]
     */
    public function getOneOf()
    {
        return $this->oneOf;
    }


    /**
     * @param array $allOf
     *
     * @return ObjectProperty
     * @throws BuildException
     */
    public function addAllOf(array $allOf)
    {
        foreach ($allOf as $item) {
            $this->allOf[] = (new self())->fromArray($item);
        }

        return $this;
    }


    /**
     * @return ObjectProperty[]
     */
    public function getAllOf()
    {
        return $this->allOf;
    }

    /**
     * @param Property $anotherProperty
     *
     * @return Property
     */
    public function merge(Property $anotherProperty)
    {
        if (!($anotherProperty instanceof self)) {
            return parent::merge($anotherProperty);
        }

        $internalProperties = $anotherProperty->getProperties();
        if ($anotherProperty->getMergeStrategy() == Property::MERGE_STRATEGY_REPLACE) {
            $this->properties = null;
            foreach ($internalProperties as $property) {
                $this->addProperty($property);
            }
        } elseif ($anotherProperty->getMergeStrategy() == Property::MERGE_STRATEGY_ADD) {
            foreach ($internalProperties as $id => $internalProperty) {
                if (array_key_exists($id, $this->properties)) {
                    $this->properties[$id] = $this->properties[$id]->merge($internalProperty);
                } else {
                    $this->addProperty($internalProperty);
                }
            }
        }

        if (!empty($anotherProperty->getDefinitions())) {
            foreach ($anotherProperty->getDefinitions() as $definition) {
                $this->addDefinition($definition);
            }
        }

        return parent::merge($anotherProperty);
    }

    /**
     * {@inheritdoc}
     */
    public function display($public = true)
    {
        $base = parent::display($public);

        $base['properties'] = [];
        $base['required'] = [];
        $base['dependencies'] = [];
        $base['definitions'] = [];

        if (!empty($this->properties)) {
            foreach ($this->properties as $property) {
                if ($property instanceof NullProperty) {
                    continue;
                }
                $base['properties'][$property->getId()] = $property->display($public);

                if ($property->isRequired() && $public) {
                    $base['required'][] = $property->getId();
                }

                if ($property->hasDependency()) {
                    $base['dependencies'][$property->getDependency()][] = $property->getId();
                }
            }
        }

        foreach ($this->definitions as $id => $definition) {
            $base['definitions'][$definition->getId()] = $definition->display();
        }

        if (empty($base['required'])) {
            unset($base['required']);
        }

        if (empty($base['dependencies'])) {
            unset($base['dependencies']);
        }

        if (empty($base['definitions'])) {
            unset($base['definitions']);
        }

        if (empty($base['properties'])) {
            unset($base['properties']);
        }

        if ($this->if) {
            $base['if'] = $this->if->display();
        }

        if ($this->then) {
            $base['then'] = $this->then->display();
        }

        if ($this->source) {
            $base['source'] = $this->source->display();
        }

        return $base;
    }


    /**
     * @param array $fields
     *
     * @return $this
     * @throws BuildException
     */
    public function fromArray($fields)
    {
        parent::fromArray($fields);
        foreach ($fields as $field => $value) {
            switch ($field) {
                case 'properties':
                case 'definitions':
                    foreach ($value as $propertyId => $propertyValues) {
                        if (!array_key_exists('type', $propertyValues)) {
                            throw new BuildException("Parameter 'type' not set for property {$propertyId}");
                        }
                        $property = self::create($propertyId, $propertyValues);
                        if ($field == 'properties') {
                            $this->addProperty($property);
                        } else {
                            $this->addDefinition($property);
                        }
                    }
                    break;
                case 'required':
                    if (is_array($value)) {
                        foreach ($value as $fieldName) {
                            $this->getPropertyById($fieldName)->setRequired(true);
                        }
                    }
                    break;
                case 'if' :
                    if (!isset($fields['then'])) {
                        throw new BuildException("Parameter 'if' isset but parameter 'then' not");
                    }
                    $this->addIf((new self())->fromArray($fields['if']));

                    break;
                case 'then':
                    if (!isset($fields['if'])) {
                        throw new BuildException("Parameter 'then' isset but parameter 'if' not");
                    }
                    $this->addThen((new self())->fromArray($fields['then']));

                    break;
                case 'oneOf':
                    $this->addOneOf($value);
                    break;
                case 'allOf':
                    $this->addAllOf($value);
                    break;
                default:
                    break;
            }
        }

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $errors = parent::validate($input, $connector);

        // todo: think if we need it
        if (!is_array($input)) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                'not_array',
                "Input value type '" . gettype($input) . "' is not an array and can't be validated"
            );

            return $errors;
        }

        foreach ($this->properties as $property) {
            if (
                $property->isRequired()
                && !isset($input[$property->getId()])
            ) {
                $errors[] =
                    new SchemaValidationError(
                        $property->getFullPath(),
                        SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                        "Required property '{$property->getId()}' is missing"
                    );
            }

            $internalRequired = false;
            if ($property instanceof self) {
                foreach ($property->getProperties() as $internalProperty) {
                    if ($internalProperty->isRequired()) {
                        $internalRequired = true;
                        break;
                    }
                }
            }

            if (
                $property->hasDependency()
                && empty($input[$property->getId()])
                && !empty($input[$property->getDependency()])
                && $internalRequired
            ) {
                $errors[] =
                    new SchemaValidationError(
                        $property->getFullPath(),
                        SchemaValidationError::DEPEND_PROPERTY_EMPTY,
                        "Property '{$property->getId()}' shoud be set because " .
                        "it depends on '{$property->getDependency()}' property"
                    );
            }
        }

        foreach ($input as $field => $value) {
            if (isset($this->properties[$field])) {
                $property = $this->properties[$field];
            } else {
                continue;
            }
            if ( $this->checkDependency($input, $property) )
            {
                $errors = array_merge($errors, $property->validate($value, $connector));
            }
        }

        $errors = $this->validateIfThenCondition($input, $errors, $connector);

        return $errors;
    }

    /**
     * @param array $input
     * @param array $errors
     * @param ISchemaConnector $connector
     *
     * @return array
     * @throws BuildException
     */
    private function validateIfThenCondition(array $input, array $errors, ISchemaConnector $connector = null)
    {
        if (empty($this->if) || empty($this->then)) {
            return $errors;
        }

        if (empty($this->if->validate($input, $connector))) {

            $subErrors = $this->then->validate($input, $connector);
            if (!empty($subErrors)) {
                $errors[] = new SchemaValidationError(
                    $this->getFullPath(),
                    SchemaValidationError::THEN_CONDITION_NOT_PASSED,
                    "Input data does not match schema from 'then' condition"
                );

                foreach ($subErrors as $error) {
                    $error->setPath($this->getFullPath() . self::PROPERTY_SEPARATOR . $error->getPath());
                }

                return array_merge($errors, $subErrors);
            }

        }

        return $errors;
    }


    /**
     * {@inheritdoc}
     */
    protected function enrichValidatorFields($input)
    {
        parent::enrichValidatorFields($input);

        foreach ($this->properties as $property) {
            $property->enrichValidatorFields($input);
        }
    }

    /**
     * @param array $input
     * @param Property $property
     *
     * @return bool
     */
    protected function checkDependency($input, $property) {
        return
            !$property->hasDependency()
            || (
                $property->hasDependency()
                && !empty($input[$property->getDependency()])
            );
    }
}