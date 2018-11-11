<?php

namespace Hamlet\JsonSchemaBundle\Model\Factory;


use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\Elements\BooleanProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\EnumProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\NullProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\Property;
use Hamlet\JsonSchemaBundle\Model\Elements\ReferenceProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\StringProperty;

class PropertyFactory
{
    /**
     * @return PropertyFactory
     */
    public static function get()
    {
        return new self();
    }

    protected function __construct()
    {
    }


    /**
     * @param array $array
     *
     * @return ObjectProperty
     * @throws BuildException
     */
    public function createFromArray(array $array)
    {
        $root = $this->createObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME, $array);

        return $root;
    }

    /**
     * @param string $id
     * @param array $array
     * @param bool $isDefinition
     *
     * @return Property
     * @throws BuildException
     */
    protected function createProperty($id, $array, $isDefinition = false)
    {
        if (!array_key_exists('type', $array)) {
            throw new BuildException('Parameter TYPE should be set for ' . $id);
        }
        switch ($array['type']) {
            case 'string':
                $property = $this->createStringProperty($id, $array);
                break;
            case 'boolean':
                $property = $this->createBooleanProperty($id, $array);
                break;
            case 'object':
                $property = $this->createObjectProperty($id, $array);
                break;
            case 'reference':
            case 'ref':
                $property = $this->createReferenceProperty($id, $array);
                break;
            case 'enum':
                $property = $this->createEnumProperty($id, $array);
                break;
            case 'null':
            case null:
                $property = $this->createNullProperty($id, $array);
                break;
            default:
                throw new BuildException('unrecognised type ' . $array['type']);
        }

        if (array_key_exists('default', $array)) {
            $property->setDefault($array['default']);
        }
        if (array_key_exists('const', $array)) {
            $property->setConstant($array['const']);
        }
        if (array_key_exists('required', $array) && !$isDefinition && !is_array($array['required'])) {
            $property->setRequired($array['required']);
        }
        if (array_key_exists('readonly', $array)) {
            $property->setReadOnlyValue($array['readonly']);
        }
        if (array_key_exists('description', $array)) {
            $property->setDescription($array['description']);
        }
        if (array_key_exists('mergeStrategy', $array)) {
            $property->setMergeStrategy($array['mergeStrategy']);
        }
        if (array_key_exists('dependsOn', $array) && !$isDefinition) {
            $property->addDependency($array['dependsOn']);
        }
        if (array_key_exists('inline', $array) && $isDefinition) {
            $property->setInline($array['inline']);
        }

        return $property;
    }

    /**
     * @param string $id
     * @param array $array
     *
     * @return StringProperty
     */
    protected function createStringProperty($id, $array)
    {
        $property = new StringProperty($id);
        if (array_key_exists('pattern', $array)) {
            $property->setPattern($array['pattern']);
        }
        if (array_key_exists('format', $array)) {
            $property->setFormat($array['format']);
        }
        if (array_key_exists('minLength', $array)) {
            $property->setMinLength($array['minLength']);
        }
        if (array_key_exists('maxLength', $array)) {
            $property->setMaxLength($array['maxLength']);
        }

        return $property;
    }

    /**
     * @param string $id
     * @param array $array
     *
     * @return BooleanProperty
     */
    protected function createBooleanProperty($id, $array)
    {
        $property = new BooleanProperty($id);

        return $property;
    }


    /**
     * @param string $id
     * @param array  $array
     *
     * @return ObjectProperty
     * @throws BuildException
     */
    protected function createObjectProperty($id, array $array)
    {
        $property = (new ObjectProperty($id))->fromArray($array);

        if (!empty($array['definitions'])) {
            foreach ($array['definitions'] as $id => $definition) {
                $property->addDefinition($this->createProperty($id, $definition, true));
            }
        }

        return $property;
    }

    /**
     * @param string $id
     * @param array $array
     *
     * @return ReferenceProperty
     */
    protected function createReferenceProperty($id, $array)
    {
        $property = new ReferenceProperty($id);

        if (array_key_exists('ref', $array)) {
            $property->setReference($array['ref']);
        }
        if (array_key_exists('$ref', $array)) {
            $property->setReference($array['$ref']);
        }

        return $property;
    }

    /**
     * @param string $id
     * @param array $array
     *
     * @return EnumProperty
     */
    protected function createEnumProperty($id, $array)
    {
        $property = new EnumProperty($id);

        if (array_key_exists('options', $array)) {
            $property->setOptions($array['options']);
        }

        return $property;
    }

    /**
     * @param string $id
     * @param array $array
     *
     * @return NullProperty
     */
    protected function createNullProperty($id, $array)
    {
        return new NullProperty($id);
    }
}