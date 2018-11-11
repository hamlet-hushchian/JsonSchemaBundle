<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;

use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

class StringProperty extends Property
{
    const FORMAT = 'format';

    const MAX_LENGTH = 'maxLength';

    const MIN_LENGTH = 'minLength';

    const PATTERN = 'pattern';

    /** @var string */
    protected $type = 'string';

    /** @var string */
    protected $pattern;

    /** @var int */
    protected $maxLength;

    /** @var int */
    protected $minLength;

    /** @var string */
    protected $format;

    /**
     * @param string $pattern
     *
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param int $maxLength
     *
     * @return $this
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param int $minLength
     *
     * @return $this
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;

        return $this;
    }

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @param string $format
     *
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
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
            $this->setMaxLength($anotherProperty->getMaxLength());
            $this->setMinLength($anotherProperty->getMinLength());
            $this->setFormat($anotherProperty->getFormat());
            $this->setPattern($anotherProperty->getPattern());
        } elseif ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_ADD) {
            if (null !== $anotherProperty->getMaxLength()) {
                $this->setMaxLength($anotherProperty->getMaxLength());
            }
            if (null !== $anotherProperty->getMinLength()) {
                $this->setMinLength($anotherProperty->getMinLength());
            }
            if (null !== $anotherProperty->getFormat()) {
                $this->setFormat($anotherProperty->getFormat());
            }
            if (null !== $anotherProperty->getPattern()) {
                $this->setPattern($anotherProperty->getPattern());
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

        if (null !== $this->getFormat()) {
            $base[self::FORMAT] = $this->getFormat();
        }

        if (null !== $this->getMaxLength()) {
            $base[self::MAX_LENGTH] = $this->getMaxLength();
        }

        if (null !== $this->getMinLength()) {
            $base[self::MIN_LENGTH] = $this->getMinLength();
        }

        if (null !== $this->getPattern()) {
            $base[self::PATTERN] = $this->getPattern();
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
                case self::FORMAT:
                    $this->setFormat($value);
                    break;
                case self::MAX_LENGTH:
                    $this->setMaxLength($value);
                    break;
                case self::MIN_LENGTH:
                    $this->setMinLength($value);
                    break;
                case self::PATTERN:
                    $this->setPattern($value);
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
        if (!is_null($this->getMaxLength()) && mb_strlen($input) > $this->getMaxLength()) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::MORE_THAN_MAX_LENGTH,
                "Value length '" . mb_strlen($input) . "' is greater than expected '{$this->getMaxLength()}'"
            );
        }
        if (!is_null($this->getMinLength()) && mb_strlen($input) < $this->getMinLength()) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::LESS_THAN_MIN_LENGTH,
                "Value length '" . mb_strlen($input) . "' is less than expected '{$this->getMinLength()}'"
            );
        }
        if (!is_null($this->getPattern()) && !preg_match($this->getPattern(), $input)) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::REGEXP_IS_NOT_MATCH,
                "Value '" . $input . "' is not matching expected pattern '{$this->getPattern()}'"
            );
        }

        return $errors;
    }
}