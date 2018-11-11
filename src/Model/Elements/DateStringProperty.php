<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class DateStringProperty extends StringProperty
{

    const MIN_VALUE = 'minValue';
    const MAX_VALUE = 'maxValue';

    /** @var string */
    protected $format = 'date';

    /** @var int */
    protected $minValue = null;

    /** @var int */
    protected $maxValue = null;


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
                case self::MIN_VALUE:
                    $this->setMinValue($value);
                    break;
                case self::MAX_VALUE:
                    $this->setMaxValue($value);
                    break;
                default:
                    break;
            }
        }

        return $this;

    }

    /**
     * @param string $value - DateTime compatible format (e.g. "1 day", "10 year", "6 month")
     *
     * @return $this
     * @throws BuildException
     */
    public function setMinValue($value)
    {
        try {
            $this->minValue = (new \DateTime($value))->format('Y-m-d');
        } catch (\Exception $e) {
            throw new BuildException("{$value} for minValue in {$this->id} is in wrong format");
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMinValue()
    {
        return $this->minValue;
    }

    /**
     * @param string $value - DateTime compatible format (e.g. "1 day", "10 year", "6 month")
     *
     * @return $this
     * @throws BuildException
     */
    public function setMaxValue($value)
    {
        try {
            $this->maxValue = (new \DateTime($value))->format('Y-m-d');
        } catch (\Exception $e) {
            throw new BuildException("{$value} for maxValue in {$this->id} is in wrong format");
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * {@inheritdoc}
     */
    public function display($public = true)
    {
        $base = parent::display($public);

        if (null !== $this->getMinValue()) {
            $base[self::MIN_VALUE] = $this->getMinValue();
        }
        if (null !== $this->getMaxValue()) {
            $base[self::MAX_VALUE] = $this->getMaxValue();
        }

        return $base;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $errors = parent::validate($input, $connector);

        try {
            $date = new \DateTime($input);
        } catch (\Exception $e) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::NOT_VALID_DATE,
                "Value '{$input}' does not in valid date format"
            );

            return $errors;
        }

        $now = (new \DateTime('now'));

        if (null !== $this->getMinValue()) {
            $minValue = new \DateTime($this->getMinValue());
            if (
                (($minValue < $now) && ($date > $minValue))
                ||
                (($minValue >= $now) && ($date < $minValue))
            ) {
                $errors[] = new SchemaValidationError(
                    $this->getFullPath(),
                    SchemaValidationError::LESS_THAN_MIN_VALUE,
                    "Date '{$input}' does not match minValue ({$this->getMinValue()}) constraint"
                );
            }
        }

        if (null !== $this->getMaxValue()) {
            $maxValue = new \DateTime($this->getMaxValue());
            if (
                (($maxValue > $now) && ($date > $maxValue))
                ||
                (($maxValue <= $now) && ($date < $maxValue))
            ) {
                $errors[] = new SchemaValidationError(
                    $this->getFullPath(),
                    SchemaValidationError::MORE_THAN_MAX_VALUE,
                    "Date '{$input}' does not match maxValue ({$this->getMaxValue()}) constraint"
                );
            }
        }

        return $errors;
    }


    /**
     * @param Property $anotherProperty
     *
     * @return Property
     * @throws BuildException
     */
    public function merge(Property $anotherProperty)
    {
        if (!($anotherProperty instanceof self)) {
            return parent::merge($anotherProperty);
        }

        if ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_REPLACE) {
            $this->setMinValue($anotherProperty->getMinValue());
            $this->setMaxValue($anotherProperty->getMaxValue());
        } elseif ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_ADD) {
            if ($anotherProperty->getMinValue() !== null) {
                $this->setMinValue($anotherProperty->getMinValue());
            }
            if ($anotherProperty->getMaxValue() !== null) {
                $this->setMaxValue($anotherProperty->getMaxValue());
            }
        }

        return parent::merge($anotherProperty);
    }
}