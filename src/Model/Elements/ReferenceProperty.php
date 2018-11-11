<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class ReferenceProperty extends Property
{
    protected $type = 'ref';

    /** @var string */
    protected $reference = null;

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setReference($path)
    {
        $this->reference = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }


    /**
     * @param array $fields
     *
     * @return $this
     * @throws \Hamlet\JsonSchemaBundle\Model\BuildException
     */
    public function fromArray($fields)
    {
        parent::fromArray($fields);
        foreach ($fields as $propertyId => $propertyValues) {
            switch ($propertyId) {
                case 'ref':
                case '$ref':
                    $this->setReference($propertyValues);
                    break;
                case self::INLINE:
                    $this->setInline($propertyValues);
                    break;
                default:
                    break;
            }
        }

        return $this;
    }

    /**
     * @param Property $anotherProperty
     *
     * @return Property
     */
    public function merge(Property $anotherProperty)
    {
        if ($anotherProperty instanceof self && !is_null($anotherProperty->getReference())) {
            $this->setReference($anotherProperty->getReference());
        }

        return parent::merge($anotherProperty);
    }

    /**
     * {@inheritdoc}
     */
    public function display($public = true)
    {
        $base = parent::display($public);

        if (null !== $this->getReference()) {
            $base['$ref'] = '#/definitions/' . $this->getReference();
        }

        return $base;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $errors = parent::validate($input, $connector);
        /** @var ObjectProperty $root */
        $root = $this->getRoot();
        $definitions = $root->getDefinitions();
        if (!empty($definitions[$this->getReference()])) {
            $definitionErrors = $definitions[$this->getReference()]->validate($input);
            if (!empty($definitionErrors)) {
                foreach ($definitionErrors as $definitionError) {
                    $errors[] = new SchemaValidationError(
                        $this->getFullPath(),
                        $definitionError->getCode(),
                        $definitionError->getMessage()
                    );
                }
            }
        } else {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::DEFINITION_DOES_NOT_EXIST,
                "Definition {$this->getReference()} does not exist"
            );
        }

        return $errors;
    }
}