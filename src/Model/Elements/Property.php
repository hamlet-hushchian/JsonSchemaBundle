<?php

namespace Hamlet\JsonSchemaBundle\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\FieldValidator;
use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\Source;
use Hamlet\JsonSchemaBundle\Model\ValidationErrorOneOf;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;

abstract class Property
{
    const MERGE_STRATEGY_ADD = 'add';
    const MERGE_STRATEGY_REPLACE = 'replace'; // remove and add
    const MERGE_STRATEGY_REMOVE = 'remove';

    const PROPERTY_SEPARATOR = '/';

    const F_DEFAULT = 'default';

    const F_FIELD_CONST = 'const';

    const REQUIRED = 'required';

    const READONLY = 'readonly';

    const DESCRIPTION = 'description';

    const VALIDATORS = 'validators';

    const SOURCE = 'source';

    const MERGE_STRATEGY = 'mergeStrategy';

    const DEPENDS_ON = 'dependsOn';

    const TYPE = 'type';

    const INLINE = 'inline';

    const ONE_OF = 'oneOf';

    const ALL_OF = 'allOf';

    const ANY_OF = 'anyOf';

    const NOT = 'not';

    /** @var array */
    protected $generalFieldTypes = [
        self::F_DEFAULT,
        self::F_FIELD_CONST,
        self::REQUIRED,
        self::READONLY,
        self::DESCRIPTION,
        self::MERGE_STRATEGY,
        self::TYPE,
        self::DEPENDS_ON,
        self::INLINE,
        self::VALIDATORS,
    ];

    /** @var array */
    protected $possibleContainerTypes = [
        self::ALL_OF,
        self::ANY_OF,
        self::ONE_OF,
        self::NOT,
    ];

    /** @var string */
    protected $id;

    /** @var string */
    protected $type;

    /** @var bool */
    protected $required = null;

    /** @var bool */
    protected $inline = null;

    /** @var string */
    protected $description = null;

    /** @var mixed */
    protected $defaultValue = null;

    /** @var mixed */
    protected $constantValue = null;

    /** @var string */
    protected $dependsOn = null;

    /** @var string */
    protected $mergeStrategy = self::MERGE_STRATEGY_ADD;

    /** @var Property|null */
    protected $parent = null;

    /** @var FieldValidator[] */
    protected $validators = [];

    /** @var Source */
    protected $source;

    /** @var bool */
    protected $validatorsEnriched;

    /** @var string|null */
    protected $containerType;

    /** @var Property[] */
    protected $containerItems;

    /**
     * @param string $propertyId
     * @param array $propertyValues
     *
     * @return $this
     * @throws BuildException
     */
    final public static function create($propertyId, $propertyValues)
    {
        switch ($propertyValues['type']) {
            case 'string':
                if (!empty($propertyValues['format']) && $propertyValues['format'] == 'email') {
                    $property = (new EmailStringProperty($propertyId))->fromArray($propertyValues);
                } elseif (!empty($propertyValues['format']) && $propertyValues['format'] == 'date') {
                    $property = (new DateStringProperty($propertyId))->fromArray($propertyValues);
                } else {
                    $property = (new StringProperty($propertyId))->fromArray($propertyValues);
                }
                break;
            case 'boolean':
                $property = (new BooleanProperty($propertyId))->fromArray($propertyValues);
                break;
            case 'object':
                $property = (new ObjectProperty($propertyId))->fromArray($propertyValues);
                break;
            case 'enum':
                $property = (new EnumProperty($propertyId))->fromArray($propertyValues);
                break;
            case 'null':
            case null:
                $property = (new NullProperty($propertyId))->fromArray($propertyValues);
                break;
            case 'reference':
            case 'ref':
                $property = (new ReferenceProperty($propertyId))->fromArray($propertyValues);
                break;
            default:
                throw new BuildException("Unknown property type '{$propertyValues['type']}'");
        }

        return $property;
    }

    /**
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param bool $required
     *
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }


    /**
     * @param boolean $inline
     *
     * @return $this
     */
    public function setInline($inline)
    {
        $this->inline = $inline;

        return $this;
    }


    /**
     * @return boolean
     */
    public function isInline()
    {
        return $this->inline;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $defaultValue
     *
     * @return $this
     */
    public function setDefault($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $constantValue
     *
     * @return $this
     */
    public function setConstant($constantValue)
    {
        $this->constantValue = $constantValue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConstant()
    {
        return $this->constantValue;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setReadOnlyValue($value)
    {
        $this->setConstant($value)->setDefault($value);

        return $this;
    }

    /**
     * @param string $from
     *
     * @return $this
     */
    public function addDependency($from)
    {
        $this->dependsOn = $from;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasDependency()
    {
        return (!empty($this->dependsOn));
    }

    /**
     * @return string
     */
    public function getDependency()
    {
        return $this->dependsOn;
    }


    /**
     * @param Property $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Property
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        $parentProperty = $this->getParent();
        $path = $this->getId();
        while ($parentProperty !== null) {
            if($parentProperty->getId() !== '')
                $path = $parentProperty->getId() . self::PROPERTY_SEPARATOR . $path;
            $parentProperty = $parentProperty->getParent();
        }

        return $path;
    }

    /**
     * @return Property
     */
    public function getRoot()
    {
        $parentProperty = $this->getParent();

        while ($parentProperty->getId() !== ObjectProperty::ROOT_PROPERTY_NAME) {
            $parentProperty = $parentProperty->getParent();
        }

        return $parentProperty;
    }

    /**
     * @param string $id
     *
     * @return Property
     * @throws BuildException
     */
    public function getPropertyById($id)
    {
        throw new BuildException("Item " . ($this->getId()) . " can not have properties. [{$id}]");
    }


    /**
     * @param string $strategy
     *
     * @return $this
     * @throws BuildException
     */
    public function setMergeStrategy($strategy = self::MERGE_STRATEGY_ADD)
    {
        if (!in_array(
            $strategy,
            [self::MERGE_STRATEGY_ADD, self::MERGE_STRATEGY_REPLACE, self::MERGE_STRATEGY_REMOVE]
        )
        ) {
            throw new BuildException("Unknown stragegy: '{$strategy}'");
        }
        $this->mergeStrategy = $strategy;

        return $this;
    }

    /**
     * @return string
     */
    public function getMergeStrategy()
    {
        return $this->mergeStrategy;
    }

    /**
     * @param Property $anotherProperty
     *
     * @return $this
     */
    public function merge(Property $anotherProperty)
    {
        if ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_REMOVE) {
            return (new NullProperty($anotherProperty->getId()))->setParent($this->getParent());
        }

        if ($anotherProperty->getType() !== $this->getType()) {
            return $anotherProperty;
        }

        if ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_REPLACE) {
            $this->validators = $anotherProperty->getValidators();
            $this->setDescription($anotherProperty->getDescription());
            $this->setRequired($anotherProperty->isRequired());
            $this->setDefault($anotherProperty->getDefault());
            $this->setConstant($anotherProperty->getConstant());
            $this->setInline($anotherProperty->isInline());
        } elseif ($anotherProperty->getMergeStrategy() == self::MERGE_STRATEGY_ADD) {
            $this->validators = array_merge($this->validators, $anotherProperty->getValidators());

            if (null !== $anotherProperty->getDescription()) {
                $this->setDescription($anotherProperty->getDescription());
            }
            if (null !== $anotherProperty->isRequired()) {
                $this->setRequired($anotherProperty->isRequired());
            }
            if (null !== $anotherProperty->getDefault()) {
                $this->setDefault($anotherProperty->getDefault());
            }
            if (null !== $anotherProperty->getConstant()) {
                $this->setConstant($anotherProperty->getConstant());
            }
            if (null !== $anotherProperty->isInline()) {
                $this->setInline($anotherProperty->isInline());
            }
        }

        return $this;
    }


    /**
     * @param bool $public
     *
     * @return array
     * @throws BuildException
     */
    public function display($public = true)
    {
        if (empty($this->type)) {
            throw new BuildException('Parameter TYPE should be set for ' . $this->getId());
        }

        $result = [
            'type' => $this->type,
        ];

        if (null !== $this->getConstant()) {
            $result['const'] = $this->getConstant();
        }

        if (null !== $this->getDefault()) {
            $result['default'] = $this->getDefault();
        }

        if (null !== $this->getDescription()) {
            $result['description'] = $this->getDescription();
        }

        if (null !== $this->isInline()) {
            $result['inline'] = $this->isInline();
        }

        if (!$public && null !== $this->isRequired()) {
            $result['required'] = $this->isRequired();
        }

        if (self::MERGE_STRATEGY_ADD !== $this->getMergeStrategy()) {
            $result['mergeStrategy'] = $this->getMergeStrategy();
        }

        if (!empty($this->getValidators())) {
            foreach ($this->validators as $validator) {
                // todo: check field existence
                $result['validators'][] = $validator->display();
            }
        }

        if (!empty($this->containerType)) {
            foreach ($this->containerItems as $containerItem) {
                $result[$this->containerType][] = $containerItem->display();
            }
        }

        return $result;
    }


    /**
     * @param array $fields
     *
     * @return $this
     * @throws BuildException
     */
    public function fromArray($fields)
    {
        foreach ($fields as $field => $value) {
            switch ($field) {
                case self::F_DEFAULT:
                    $this->setDefault($value);
                    break;
                case self::F_FIELD_CONST:
                    $this->setConstant($value);
                    break;
                case self::REQUIRED:
                    $this->setRequired($value);
                    break;
                case self::READONLY:
                    $this->setReadOnlyValue($value); // todo: use readonly field separately
                    break;
                case self::DESCRIPTION:
                    $this->setDescription($value);
                    break;
                case self::MERGE_STRATEGY:
                    $this->setMergeStrategy($value);
                    break;
                case self::DEPENDS_ON:
                    $this->addDependency($value);
                    break;
                case self::VALIDATORS:
                    foreach ($value as $validatorFields) {
                        $this->addValidator(FieldValidator::fromArray($validatorFields));
                    }
                    break;
                case self::SOURCE :
                    $this->addSource(Source::fromArray($value));
                    break;
                case self::ONE_OF:
                case self::ALL_OF:
                case self::ANY_OF:
                case self::NOT:
                    $this->setContainerType($field);
                    foreach ($value as $containerItem) {
                        $this->addContainerItem(self::create('', $containerItem));
                    }
                    break;
            }
        }

        return $this;
    }

    /**
     * @return Property[]
     */
    public function getContainerItems()
    {
        return $this->containerItems;
    }

    /**
     * @param Property $item
     *
     * @return $this
     */
    public function addContainerItem(Property $item)
    {
        $item->setParent($this);
        $this->containerItems[] = $item;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContainerType()
    {
        return $this->containerType;
    }


    /**
     * @param $containerType
     *
     * @throws BuildException
     * @return $this
     */
    public function setContainerType($containerType)
    {
        if (!in_array($containerType, $this->possibleContainerTypes)) {
            throw new BuildException("Container type {$containerType} is not supported");
        }
        $this->containerType = $containerType;

        return $this;
    }

    /**
     * @param FieldValidator $validator
     */
    public function addValidator(FieldValidator $validator)
    {
        $this->validators[$validator->getUrl()] = $validator;
    }

    /**
     * @param Source $source
     *
     * @return $this
     */
    public function addSource(Source $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return FieldValidator[]
     */
    public function getValidators()
    {
        return $this->validators;
    }


    /**
     * @param array|string $input
     *
     * @param ISchemaConnector $connector
     *
     * @return SchemaValidationError[]
     * @throws BuildException
     * @throws \Exception
     */
    public function validate($input, ISchemaConnector $connector = null)
    {
        $this->enrichValidatorFields($input);

        $errors = [];

        if (!is_null($this->getConstant()) && $input != $this->getConstant()) {
            $errors[] = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::NOT_EQUALS_TO_CONST,
                "Value '{$input}' failed const constraint ({$this->getConstant()})"
            );
        }

        if (!empty($this->containerType)) {
            switch ($this->containerType) {
                case self::ONE_OF:
                    $errors = array_merge($errors, $this->validateOneOf($input, $connector));
                    break;
                case self::ANY_OF:
                    $errors = array_merge($errors, $this->validateAnyOf($input, $connector));
                    break;
                case self::ALL_OF:
                    $errors = array_merge($errors, $this->validateAllOf($input, $connector));
                    break;
                case self::NOT:
                    $errors = array_merge($errors, $this->validateNot($input, $connector));
                    break;
            }
        }

        if (!empty($this->validators)) {
            $this->ensure(!empty($connector), "Field validator connector not set");
            foreach ($this->validators as $validator) {
                $extErrors = $validator->validate($connector);
                foreach ($extErrors as $error) {
                    $error->setPath($this->getFullPath());
                }
                $errors = array_merge($errors, $extErrors);
            }
        }

        if (!empty($this->source)) {
            $this->ensure(!empty($connector), "Source connector not set");
            $this->enrichSourceFields($input);
            $extErrors = $this->source->getSchema($connector)->validate($input);
            foreach ($extErrors as $error) {
                $error->setPath($this->getFullPath());
            }
            $errors = array_merge($errors, $extErrors);
        }

        return $errors;
    }

    /**
     * @param array|string $input
     *
     * @param ISchemaConnector $connector
     *
     * @return SchemaValidationError[]
     * @throws BuildException
     */
    protected function validateOneOf($input, ISchemaConnector $connector = null)
    {
        $alreadyValidated = false;
        $errorTitle = false;
        $caseErrors = [];
        foreach ($this->containerItems as $item)
        {
            $errors = $item->validate($input, $connector);

            if (!empty($errors))
            {
                $caseErrors[] = $errors;
            }
            else
            {
                if (!$alreadyValidated)
                {
                    $alreadyValidated = true;
                    continue;
                }
                else
                {
                    return [
                        new SchemaValidationError(
                            $this->getFullPath(),
                            SchemaValidationError::SATISFY_MULTIPLE_CASES_ONE_OF,
                            "Given value satisfy multiple validation schemas"
                        ),
                    ];
                }
            }
        }

        if(!empty($caseErrors))
        {
            $errorTitle = new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed {$this->getContainerType()} validation"
            );
        }

        return $alreadyValidated
            ? []
            :  (new ValidationErrorOneOf($caseErrors, $errorTitle))->render();
    }

    /**
     * @param array|string $input
     *
     * @param ISchemaConnector $connector
     *
     * @return SchemaValidationError[]
     * @throws BuildException
     */
    protected function validateAnyOf($input, ISchemaConnector $connector = null)
    {
        foreach ($this->containerItems as $item) {
            $errors = $item->validate($input, $connector);
            if (empty($errors)) {
                return [];
            }
        }

        return [
            new SchemaValidationError(
                $this->getFullPath(),
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed {$this->getContainerType()} validation"
            ),
        ];
    }


    /**
     * @param array|string     $input
     *
     * @param ISchemaConnector $connector
     *
     * @return SchemaValidationError[]
     * @throws BuildException
     */
    protected function validateAllOf($input, ISchemaConnector $connector = null)
    {
        foreach ($this->containerItems as $item) {
            $errors = $item->validate($input, $connector);
            if (!empty($errors)) {
                $allOfError = new SchemaValidationError(
                    $this->getFullPath(),
                    SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                    "Given value does not passed {$this->getContainerType()} validation"
                );
                array_unshift($errors, $allOfError);

                return $errors;
            }
        }

        return [];
    }

    /**
     * @param array|string $input
     *
     * @param ISchemaConnector $connector
     *
     * @return SchemaValidationError[]
     * @throws BuildException
     */
    protected function validateNot($input, ISchemaConnector $connector = null)
    {
        foreach ($this->containerItems as $item) {
            $errors = $item->validate($input, $connector);
            if (empty($errors)) {
                return [
                    new SchemaValidationError(
                        $this->getFullPath(),
                        SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                        "Given value does not passed {$this->getContainerType()} validation"
                    ),
                ];
            }
        }

        return [];
    }

    /**
     * @param array|string $input
     *
     * @throws \Exception
     */
    protected function enrichValidatorFields($input)
    {
        if (!$this->validatorsEnriched) {
            foreach ($this->validators as $validator) {
                foreach ($validator->getFields() as $fieldName => $fieldPath) {
                    $path = $fieldPath == 'self' ? $this->getFullPath() : $fieldPath;
                    if (is_array($input)) {
                        $validator->setFieldValue($fieldName, $this->getInputValueByPath($path, $input));
                    } else {
                        $validator->setFieldValue($fieldName, $input);
                    }
                }
            }
            $this->validatorsEnriched = true;
        }
    }

    /**
     * @param array $input
     *
     * @throws \Exception
     */
    protected function enrichSourceFields($input)
    {
        foreach($this->source->getFields() as $fieldName => $fieldPath)
        {
            $this->source->setFieldValue($fieldName, $this->getInputValueByPath($fieldPath, $input));
        }
    }

    /**
     * @param string $path
     * @param array $input
     *
     * @return string
     * @throws \Exception
     */
    private function getInputValueByPath($path, $input)
    {
        $subPath = explode(self::PROPERTY_SEPARATOR, $path);
        $current = array_shift($subPath);

        // todo: think if we need it
        if ($current == ObjectProperty::ROOT_PROPERTY_NAME) {
            $current = array_shift($subPath);
        }
        if (!isset($input[$current])) {
            // throw new \Exception("Key '{$current}' not found in input array.");
            return '';
        }
        if (empty($subPath)) {
            return $input[$current];
        }
        if (!is_array($input[$current])) {
            //return '';
            throw new \Exception("Destination path is not array");
        }

        return $this->getInputValueByPath(implode(self::PROPERTY_SEPARATOR, $subPath), $input[$current]);
    }


    /**
     * @param bool $statement
     * @param string $errorText
     *
     * @throws BuildException
     */
    protected function ensure($statement, $errorText)
    {
        if (!$statement) {
            throw new BuildException($errorText);
        }
    }
}