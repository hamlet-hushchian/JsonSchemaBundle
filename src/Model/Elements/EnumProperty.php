<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class EnumProperty extends Property
{
    const OPTIONS = 'options';

    /** @var string */
    protected $type = 'enum';

    /** @var array */
    protected $options;

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }


    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
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
                case self::OPTIONS:
                    $this->setOptions($value);
                    break;
                case self::INLINE:
                    $this->setInline($value);
                    break;
                default:
                    if (!in_array($field, $this->generalFieldTypes)) {
                        throw new BuildException("Unknown field '{$field}' in " . self::class);
                    }
            }
        }

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function display($public = true)
    {
        $base = parent::display($public);

        return array_merge($base, ['options' => $this->options]);
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

        if ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_REPLACE) {
            $this->setOptions($anotherProperty->getOptions());
        } elseif ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_ADD) {
            $this->setOptions(array_merge($this->getOptions(), $anotherProperty->getOptions()));
        }

        return parent::merge($anotherProperty);
    }


    /**
     * {@inheritdoc}
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $errors = parent::validate($input, $connector);

        if (!in_array($input, $this->getOptions())) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::NOT_IN_OPTIONS,
                "Value '{$input}' is not present in allowed options: '[" . implode('|', $this->getOptions()) . "]'"
            );
        }

        return $errors;
    }
}