<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Elements;

use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\Config\YAMLConfig;
use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\StringProperty;
use Hamlet\JsonSchemaBundle\Model\ISchemaConnector;
use Hamlet\JsonSchemaBundle\Model\JsonSchemaBuilder;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;


class ObjectPropertyTest extends \PHPUnit_Framework_TestCase
{
    const IF_THEN_CONFIG = "/../../schema/if_then_config.yml";

    const IF_THEN_NESTED_CONFIG = "/../../schema/if_then_nested_config.yml";

    const ONE_OF_WITH_IF_THEN_CONFIG = "/../../schema/one_of_with_if_then_config.yml";

    const ALL_OF_WITH_IF_THEN_CONFIG = "/../../schema/all_of_with_if_then_config.yml";


    public function test_it_return_property_by_id()
    {
        $schema = new ObjectProperty('root');
        $schema->addProperty(new StringProperty('test'));
        $schema->addProperty(new StringProperty('test2'));

        $property = $schema->getPropertyById('test2');

        $this->assertNotEmpty($property);
        $this->assertEquals('test2', $property->getId());
    }


    public function test_it_return_nested_property()
    {
        $schema = new ObjectProperty('root');
        $schema->addProperty(
            (new ObjectProperty('field'))
                ->addProperty(
                    (new ObjectProperty('subItem'))
                        ->addProperty(
                            (new StringProperty('nested'))
                                ->setDescription('descr')
                        )
                )
        );

        $property = $schema->getPropertyById('field/subItem/nested');

        $this->assertNotEmpty($property);
        $this->assertEquals('nested', $property->getId());
        $this->assertEquals('descr', $property->getDescription());
    }


    public function test_it_throw_exception_on_non_existed_property()
    {
        $schema = new ObjectProperty('root');
        $schema->addProperty(new StringProperty('test'));

        $this->setExpectedException(BuildException::class);

        $schema->getPropertyById('nonexist');
    }


    public function test_it_create_from_array()
    {
        $property = new ObjectProperty('root');
        $property->fromArray(
            [
                'properties' => [
                    'someField'   => [
                        'type'      => 'string',
                        'maxLength' => '30',
                    ],
                    'objectField' => [
                        'type'       => 'object',
                        'properties' => [
                            'innerField' => [
                                'type' => 'boolean',
                            ],
                        ],
                        'dependsOn'  => 'someField',
                    ],
                ],
                'required'   => ['someField'],
            ]
        );

        $this->assertEquals('string', $property->getPropertyById('someField')->getType());
        $this->assertTrue($property->getPropertyById('someField')->isRequired());
        $this->assertTrue($property->getPropertyById('objectField')->hasDependency());
        $this->assertEquals('boolean', $property->getPropertyById('objectField/innerField')->getType());
    }

    /**
     * @throws BuildException
     */
    public function test_it_validate_if_then_condition()
    {
        $property = new ObjectProperty('root');

        $externalProperty = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $externalSchema = $externalProperty->fromArray(
            [
                'properties' => [
                    'firstName' => [
                        'type'    => 'string',
                        'default' => 'Hamlet',
                        'const'   => 'Hamlet',
                    ],
                ],
                'required'   => ['firstName'],
            ]
        );

        $connectorMock = $this->getMockBuilder(ISchemaConnector::class)->getMock();
        $connectorMock->method('getSchema')->will($this->returnValue($externalSchema));

        $property->fromArray(
            [
                'properties' => [
                    'firstName'      => [
                        'type' => 'string',
                    ],
                    'secondName'     => [
                        'type' => 'string',
                    ],
                    "personalNumber" => [
                        "type" => "string",
                    ],
                ],
                "required"   => [
                    "firstName",
                    "secondName",
                ],
                'if'         => [
                    'properties' => [
                        'personalNumber' => [
                            'type' => 'string',
                        ],
                    ],
                    'required'   => ['personalNumber'],
                ],
                'then' => [
                    'source' => [
                        'url'    => 'https://some.url/test',
                        'method' => 'post',
                        'fields' => [
                            'personalNumber' => '#/personalNumber',
                        ],
                    ],
                ],
            ]
        );

        $errors = $property->validate(
            [
                'firstName'  => 'hamlet',
                'secondName' => 'hushchian',
                'personalNumber' => '12345',
            ],
            $connectorMock
        );

        $this->assertNotEmpty($errors);
        $this->assertCount(2, $errors);
        $this->assertEquals(SchemaValidationError::THEN_CONDITION_NOT_PASSED, $errors[0]->getCode());
        $this->assertEquals('root', $errors[0]->getPath());
        $this->assertEquals('schema.validation.readonly', $errors[1]->getCode());
    }


    /**
     * @dataProvider inputDataProvider
     *
     * @param array $input
     * @param array $expected_errors
     *
     * @throws BuildException
     */
    public function test_it_validate_if_then_condition_from_yml($input, $expected_errors)
    {
        $builder = new JsonSchemaBuilder();
        $builder->addConfig(new YAMLConfig(__DIR__ . self::IF_THEN_CONFIG));

        /** @var SchemaValidationError[] $errors */
        $errors = $builder->getRoot()->validate($input);
        $errors_count = count($errors);

        $this->assertEquals(count($expected_errors), $errors_count);
        for ($i = 0; $i < $errors_count; $i++) {
            $this->assertEquals($expected_errors[$i], $errors[$i]->getCode());
        }
    }


    /**
     * @return array
     */
    public function inputDataProvider()
    {
        $input1 = [
            'firstName'         => 'Vasa',
            'lastName'          => 'Pupkin',
            'email'             => 'vasa@sixt.com',
            'phoneNumberMaster' => true,
            'phoneNumber'       => '380991234578',
        ];
        $errors1 = [];

        $input2 = [
            'firstName'         => 'Vasa',
            'lastName'          => 'Pupkin',
            'email'             => 'vasa@sixt.com',
            'phoneNumberMaster' => true,
        ];
        $errors2 = [
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::REQUIRED_PROPERTY_MISSING,
        ];

        return [
            [$input1, $errors1],
            [$input2, $errors2],
        ];
    }


    /**
     * @dataProvider if_then_nested_data_provider
     *
     * @param array $input
     *
     * @param array $expected_errors
     *
     * @throws BuildException
     */
    public function test_it_validate_if_then_nested_condition_from_yml($input, $expected_errors)
    {
        $builder = new JsonSchemaBuilder();
        $builder->addConfig(new YAMLConfig(__DIR__ . self::IF_THEN_NESTED_CONFIG));

        /** @var SchemaValidationError[] $errors */
        $errors = $builder->getRoot()->validate($input);
        $errors_count = count($errors);

        $this->assertEquals($errors_count, count($expected_errors));

        for ($i = 0; $i < $errors_count; $i++){
            $this->assertEquals($expected_errors[$i], $errors[$i]->getCode());
        }
    }


    /**
     * @return array
     */
    public function if_then_nested_data_provider()
    {
        $input1 = [
            'firstName' => 'Vasa',
            'lastName' => 'Pupkin',
            'email' => 'vasa@sixt.com',
            'tenant' => 'IT',
            'codiceFiscal' => '123456',
            'itPromocode' => true,
            'promocodeValue' => '1234'
        ];
        $errors1 =  [];

        $input2 = [
            'firstName'      => 'Vasa',
            'lastName'       => 'Pupkin',
            'email'          => 'vasa@sixt.com',
            'tenant'         => 'IT',
            'itPromocode'    => true,
            'promocodeValue' => '1234',
        ];
        $errors2 = [
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::REQUIRED_PROPERTY_MISSING,
        ];

        $input3 = [
            'firstName'    => 'Vasa',
            'lastName'     => 'Pupkin',
            'email'        => 'vasa@sixt.com',
            'tenant'       => 'IT',
            'codiceFiscal' => '123456',
            'itPromocode'  => true,
        ];
        $errors3 = [
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::REQUIRED_PROPERTY_MISSING,
        ];

        return [
            [$input1, $errors1],
            [$input2, $errors2],
            [$input3, $errors3],
        ];
    }


    /**
     * @dataProvider one_of_with_if_then_data_provider
     *
     * @param array $input
     * @param array $expected_errors
     *
     * @throws BuildException
     */
    public function test_it_validate_one_of_with_if_then_condition_from_yml($input, $expected_errors)
    {
        $builder = new JsonSchemaBuilder();
        $builder->addConfig(new YAMLConfig(__DIR__ . self::ONE_OF_WITH_IF_THEN_CONFIG));

        $errors = $builder->getRoot()->validate($input);
        $errors_count = count($errors);

        $this->assertEquals($errors_count, count($expected_errors));

        for ($i = 0; $i < $errors_count; $i++){
            $this->assertEquals($expected_errors[$i], $errors[$i]->getCode());
        }
    }


    /**
     * @return array
     */
    public function one_of_with_if_then_data_provider(){
        $input1 = [
            'fname' => 'Vasa',
            'lname' => 'Pupkin',
            'tenant' => 'IT',
            'city' => 'Milan',
            'codiceFiscale' => '123',
            'partitaIva' => '456',
        ];
        $errors1 = [SchemaValidationError::SATISFY_MULTIPLE_CASES_ONE_OF];

        $input2 = [
            'fname' => 'Vasa',
            'lname' => 'Pupkin',
            'tenant' => 'IT',
            'city' => 'Milan',
            'partitaIva' => '456',
        ];
        $errors2 = [];

        $input3 = [
            'fname' => 'Vasa',
            'lname' => 'Pupkin',
            'tenant' => 'IT',
            'city' => 'Milan',
        ];
        $errors3 = [
            SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::REQUIRED_PROPERTY_MISSING,
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::REQUIRED_PROPERTY_MISSING,
        ];

        return [
            [$input1, $errors1],
            [$input2, $errors2],
            [$input3, $errors3],
        ];
    }


    /**
     * @dataProvider all_of_with_if_then_data_provider
     *
     * @param array $input
     * @param array $expected_errors
     *
     * @throws BuildException
     */
    public function test_it_validate_all_of_with_if_then_condition_from_yml($input, $expected_errors)
    {
        $builder = new JsonSchemaBuilder();
        $builder->addConfig(new YAMLConfig(__DIR__ . self::ALL_OF_WITH_IF_THEN_CONFIG));

        $errors = $builder->getRoot()->validate($input);
        $errors_count = count($errors);

        $this->assertEquals($errors_count, count($expected_errors));

        for ($i = 0; $i < $errors_count; $i++){
            $this->assertEquals($expected_errors[$i], $errors[$i]->getCode());
        }
    }


    /**
     * @return array
     */
    public function all_of_with_if_then_data_provider(){
        $input1 = [
            'fname' => 'Vasa',
            'lname' => 'Pupkin',
            'tenant' => 'IT',
            'city' => 'Milan',
            'codiceFiscale' => '123',
            'partitaIva' => '456',
        ];
        $errors1 = [];

        $input2 = [
            'fname' => 'Vasa',
            'lname' => 'Pupkin',
            'tenant' => 'IT',
            'city' => 'Milan',
            'partitaIva' => '456',
        ];
        $errors2 = [
            SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::REQUIRED_PROPERTY_MISSING,
        ];

        $input3 = [
            'fname' => 'Vasa',
            'lname' => 'Pupkin',
            'tenant' => 'IT',
            'city' => 'Milan',
        ];
        $errors3 = [
            SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
            SchemaValidationError::THEN_CONDITION_NOT_PASSED,
            SchemaValidationError::REQUIRED_PROPERTY_MISSING,
        ];

        return [
            [$input1, $errors1],
            [$input2, $errors2],
            [$input3, $errors3],
        ];
    }


    public function test_it_display_validators()
    {
        $property = new ObjectProperty('root');
        $property->addProperty(new StringProperty('test'));
        $property->fromArray(
            [
                'validators' => [
                    [
                        'id'     => 'test',
                        'url'    => '/areacode/',
                        'method' => 'POST',
                        'fields' => [
                            'areaCode' => '#/areaCode',
                            'country'  => '#/country',
                        ],
                    ],
                    [
                        'id'     => 'test-other',
                        'url'    => '/other-validator/',
                        'method' => 'POST',
                        'fields' => [
                            'someField' => 'self',
                        ],
                    ],
                ],
            ]
        );

        $result = $property->display();

        $this->assertArrayHasKey('validators', $result);
        $this->assertEquals(2, count($result['validators']));
    }


    public function test_it_enrich_property_validator()
    {
        $connector = $this->getMockBuilder(ISchemaConnector::class)
            ->disableOriginalConstructor()->getMock();
        $connector->expects($this->once())
            ->method('validate')
            ->with('/test', 'POST', ['someParam' => 'some@email.com'])
            ->will($this->returnValue([]));
        $property = new StringProperty('test');
        $property->fromArray(
            [
                'validators' => [
                    [
                        'id'     => 'test',
                        'url'    => '/test',
                        'method' => 'POST',
                        'fields' => [
                            'someParam' => 'self',
                        ],
                    ],
                ],
            ]
        );
        $property->validate('some@email.com', $connector);
    }


    public function test_it_enrich_object_property_validator()
    {
        $connector = $this->getMockBuilder(ISchemaConnector::class)
            ->disableOriginalConstructor()->getMock();
        $connector->expects($this->once())
            ->method('validate')
            ->with('/test', 'POST', ['someParam' => 'some@email.com'])
            ->will($this->returnValue([]));
        $property = new ObjectProperty('#');
        $property->fromArray(
            [
                'properties' => [
                    'email' => [
                        'type'       => 'string',
                        'validators' => [
                            [
                                'id'     => 'test',
                                'url'    => '/test',
                                'method' => 'POST',
                                'fields' => [
                                    'someParam' => 'self',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $property->validate(['email' => 'some@email.com'], $connector);
    }


    public function test_it_enrich_object_neighbour_property_validator()
    {
        $connector = $this->getMockBuilder(ISchemaConnector::class)
            ->disableOriginalConstructor()->getMock();
        $connector->expects($this->once())
            ->method('validate')
            ->with(
                '/test',
                'POST',
                [
                    'someParam'  => 'some@email.com',
                    'otherParam' => 'DE',
                ]
            )
            ->will($this->returnValue([]));
        $property = new ObjectProperty('#');
        $property->fromArray(
            [
                'properties' => [
                    'address' => [
                        'type'       => 'object',
                        'properties' => [
                            'country' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'email'   => [
                        'type'       => 'string',
                        'validators' => [
                            [
                                'id'     => 'test',
                                'url'    => '/test',
                                'method' => 'POST',
                                'fields' => [
                                    'someParam'  => 'self',
                                    'otherParam' => '#/address/country',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $input = [
            'address' => [
                'country' => 'DE',
            ],
            'email'   => 'some@email.com',
        ];
        $property->validate($input, $connector);
    }


    /**
     * @dataProvider  definitions_provider
     *
     * @param array $schema
     * @param array $input
     * @param array $expectedErrors
     *
     * @throws BuildException
     */
    public function test_it_validate_definitions($schema, $input, $expectedErrors = [])
    {
        $property = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $property->fromArray($schema);

        $errors = $property->validate($input);

        $this->assertEquals($expectedErrors, $errors, print_r($errors, true));
    }


    /**
     * @return array
     */
    public function definitions_provider()
    {
        $schema = [
            'properties'  =>
                [
                    'definedField' => [
                        'type' => 'reference',
                        'ref'  => 'someDefinition',
                    ],
                ],
            'definitions' => [
                'someDefinition' => [
                    'type'    => 'enum',
                    'options' => ['1', '2', '3'],
                ],
            ],
        ];

        $input_right = [
            'definedField' => '1',
        ];

        $input_wrong = [
            'definedField' => '6',
        ];

        $errors = [
            new SchemaValidationError(
                '#/definedField',
                SchemaValidationError::NOT_IN_OPTIONS,
                "Value '6' is not present in allowed options: '[1|2|3]'"
            ),
        ];

        return [
            [$schema, $input_right, []],
            [$schema, $input_wrong, $errors],
        ];
    }


    /**
     * @dataProvider required_and_dependencies_provider
     *
     * @param array $schema
     * @param array $input
     * @param array $expectedErrors
     *
     * @throws BuildException
     */
    public function test_it_validate_required_and_dependencies($schema, $input, $expectedErrors = [])
    {
        $property = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $property->fromArray($schema);

        $errors = $property->validate($input);

        $this->assertEquals($expectedErrors, $errors, print_r($errors, true));
    }


    /**
     * @return array
     */
    public function required_and_dependencies_provider()
    {
        $schema = [
            'properties' => [
                'someField'   => [
                    'type'      => 'string',
                    'maxLength' => '5',
                    'required'  => true,
                ],
                'objectField' => [
                    'type'       => 'object',
                    'properties' => [
                        'country'  => [
                            'type'     => 'string',
                            'required' => true,
                        ],
                        'areaCode' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'bigObject'   => [
                    'type'       => 'object',
                    'properties' => [
                        'someChildProperty' => ['type' => 'boolean'],
                        'country'           => ['type' => 'string'],
                        'childObject'       => [
                            'type'       => 'object',
                            'dependsOn'  => 'someChildProperty',
                            'properties' => [
                                'childAreaCode' => ['type' => 'string', 'required' => true],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $input_right = [
            'someField'   => '1234',
            'objectField' => [
                'country'  => 'TEST',
                'areaCode' => 'TEST',
            ],
            'bigObject'   => [
                'someChildProperty' => true,
                'country'           => 'TEST',
                'childObject'       => [
                    'childAreaCode' => 'TEST',
                ],
            ],
        ];

        $input_right2 = [
            'someField'   => '1234',
            'objectField' => [
                'country'  => 'TEST',
                'areaCode' => 'TEST',
            ],
            'bigObject'   => [
                'someChildProperty' => false,
                'country'           => 'TEST',
            ],
        ];
        $input_wrong = [
            'objectField' => [
                'country'  => 'TEST',
                'areaCode' => 'TEST',
            ],
            'bigObject'   => [
                'someChildProperty' => true,
                'county'            => 'test',
            ],
        ];

        $errors = [
            new SchemaValidationError(
                '#/someField',
                SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                "Required property 'someField' is missing"
            ),
            new SchemaValidationError(
                '#/bigObject/childObject',
                SchemaValidationError::DEPEND_PROPERTY_EMPTY,
                "Property 'childObject' shoud be set because it depends on 'someChildProperty' property"
            ),
        ];

        return [
            [$schema, $input_right, []],
            [$schema, $input_right2, []],
            [$schema, $input_wrong, $errors],
        ];
    }


    /**
     * tood: fixme
     * @_test
     * @dataProvider custom_validators_provider
     *
     * @param array $schema
     * @param array $input
     * @param array $expectedErrors
     */
    public function it_should_validate_input_value($schema, $input, $expectedErrors = [])
    {
        $property = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $property->fromArray($schema);

        $connector = $this->container->get('connector.internal');

        $errors = $property->validate($input, $connector);

        $this->assertEquals($expectedErrors, $errors, print_r($errors, true));
    }


    /**
     * @return array
     */
    public function custom_validators_provider()
    {
        $schema = [
            'properties' => [
                'someField'   => [
                    'type'      => 'string',
                    'minLength' => '5',
                ],
                'objectField' => [
                    'type'       => 'object',
                    'properties' => [
                        'country'  => [
                            'type' => 'string',
                        ],
                        'areaCode' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'bigObject'   => [
                    'type'       => 'object',
                    'properties' => [
                        'someChildProperty' => ['type' => 'string'],
                        'country'           => ['type' => 'string'],
                        'childObject'       => [
                            'type'       => 'object',
                            'properties' => [
                                'childAreaCode' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'validators' => [
                'areaCodeValidator'       => [
                    'url'        => '/validation/areacode/{country}/{areaCode}',
                    'field'      => '#/objectField/areaCode',
                    'parameters' => [
                        'country'  => '#/objectField/country',
                        'areaCode' => '#/objectField/areaCode',
                    ],
                ],
                'nestedAreaCodeValidator' => [
                    'url'        => '/validation/areacode/{country}/{areaCode}',
                    'field'      => '#/bigObject/childObject/childAreaCode',
                    'parameters' => [
                        'country'  => '#/bigObject/country',
                        'areaCode' => '#/bigObject/childObject/childAreaCode',
                    ],
                ],
            ],
        ];

        $input_wrong
            = [
            'someField'   => 'some value',
            'objectField' => [
                'country'  => 'PT',
                'areaCode' => '1234',
            ],
            'bigObject'   => [
                'someChildProperty' => 'anything',
                'country'           => 'PT',
                'childObject'       => [
                    'childAreaCode' => '1234',
                ],

            ],
        ];

        $input_right
            = [
            'someField'   => 'some value',
            'objectField' => [
                'country'  => 'PT',
                'areaCode' => '1234-123',
            ],
            'bigObject'   => [
                'someChildProperty' => 'anything',
                'country'           => 'UA',
                'childObject'       => [
                    'childAreaCode' => '12345',
                ],

            ],
        ];

        $input_right_not_set
            = [
            'someField' => 'some value',
            'bigObject' => [
                'someChildProperty' => 'anything',
                'country'           => 'UA',
                'childObject'       => [
                    'childAreaCode' => '12345',
                ],

            ],
        ];
        $errors = [
            new SchemaValidationError('#/objectField/areaCode', 'areacode_not_valid'),
            new SchemaValidationError('#/bigObject/childObject/childAreaCode', 'areacode_not_valid'),
        ];

        return [
            [$schema, $input_wrong, $errors],
            [$schema, $input_right, []],
            [$schema, $input_right_not_set, []],
        ];
    }


    /**
     * @dataProvider one_of_provider
     *
     * @param array $schema
     * @param array $input
     * @param array $expectedErrors
     *
     * @throws BuildException
     */
    public function test_it_validate_one_of_value($schema, $input, $expectedErrors = [])
    {
        $property = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $property->fromArray($schema);

        $errors = $property->validate($input);

        $this->assertEquals($expectedErrors, $errors, print_r($errors, true));
    }

    /**
     * @return array
     */
    public function one_of_provider()
    {
        $schema = [
            'properties' => [
                'objectContainer' => [
                    'type'  => 'object',
                    'oneOf' =>
                        [
                            [
                                'type'       => 'object',
                                'properties' => [
                                    'string1' => ['type' => 'string', 'const' => '1', 'required' => true],
                                    'string2' => ['type' => 'string'],
                                ],
                            ],
                            [
                                'type'       => 'object',
                                'properties' => [
                                    'string3' => ['type' => 'string', 'const' => '3', 'required' => true],
                                    'string2' => ['type' => 'string'],
                                ],
                            ],

                        ],
                ],
            ],
        ];

        $correctInput1 = [
            'objectContainer' => [
                'string1' => '1',
                'string2' => 'something',
            ],
        ];

        $correctInput2 = [
            'objectContainer' => [
                'string1' => '1',
            ],
        ];

        $correctInput3 = [
            'objectContainer' => [
                'string3' => '3',
                'string2' => 'something',
            ],
        ];

        $correctInput4 = [
            'objectContainer' => [
                'string3' => '3',
            ],
        ];

        $incorrectInput1 = [
            'objectContainer' => [
                'string1' => '3',
            ],
        ];

        $incorrectInput2 = [
            'objectContainer' => [
                'string3' => '1',
            ],
        ];

        $incorrectInput3 = [
            'objectContainer' => [
                'string1' => '3',
                'string3' => '1',
            ],
        ];

        $incorrectInput4 = [
            'objectContainer' => [
                'string2' => 'something',
            ],
        ];

        $error1 = [
            new SchemaValidationError(
                "#/objectContainer",
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed oneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string1",
                SchemaValidationError::NOT_EQUALS_TO_CONST,
                "Value '3' failed const constraint (1) according to case 0 in OneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string3",
                SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                "Required property 'string3' is missing according to case 1 in OneOf validation"
            ),
        ];

        $error2 = [
            new SchemaValidationError(
                "#/objectContainer",
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed oneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string1",
                SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                "Required property 'string1' is missing according to case 0 in OneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string3",
                SchemaValidationError::NOT_EQUALS_TO_CONST,
                "Value '1' failed const constraint (3) according to case 1 in OneOf validation"
            ),
        ];

        $error3 = [
            new SchemaValidationError(
                "#/objectContainer",
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed oneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string1",
                SchemaValidationError::NOT_EQUALS_TO_CONST,
                "Value '3' failed const constraint (1) according to case 0 in OneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string3",
                SchemaValidationError::NOT_EQUALS_TO_CONST,
                "Value '1' failed const constraint (3) according to case 1 in OneOf validation"
            ),
        ];

        $error4 = [
            new SchemaValidationError(
                "#/objectContainer",
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed oneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string1",
                SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                "Required property 'string1' is missing according to case 0 in OneOf validation"
            ),
            new SchemaValidationError(
                "#/objectContainer/string3",
                SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                "Required property 'string3' is missing according to case 1 in OneOf validation"
            ),
        ];

        return
            [
                [$schema, $correctInput1, []],
                [$schema, $correctInput2, []],
                [$schema, $correctInput3, []],
                [$schema, $correctInput4, []],
                [$schema, $incorrectInput1, $error1],
                [$schema, $incorrectInput2, $error2],
                [$schema, $incorrectInput3, $error3],
                [$schema, $incorrectInput4, $error4],
            ];
    }


    /**
     * @dataProvider any_of_provider
     *
     * @param array $schema
     * @param array $input
     * @param array $expectedErrors
     *
     * @throws BuildException
     */
    public function test_it_validate_any_of_value($schema, $input, $expectedErrors = [])
    {
        $property = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $property->fromArray($schema);

        $errors = $property->validate($input);

        $this->assertEquals($expectedErrors, $errors, print_r($errors, true));
    }

    /**
     * @return array
     */
    public function any_of_provider()
    {
        $schema = [
            'properties' => [
                'objectContainer' => [
                    'type'  => 'object',
                    'anyOf' => [
                        [
                            'type'       => 'object',
                            'properties' => [
                                'string1' => ['type' => 'string', 'const' => '1', 'required' => true],
                                'string2' => ['type' => 'string'],
                            ],
                        ],
                        [
                            'type'       => 'object',
                            'properties' => [
                                'string3' => ['type' => 'string', 'const' => '3', 'required' => true],
                                'string2' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $correctInput1 = [
            'objectContainer' => [
                'string1' => '1',
                'string2' => 'something',
            ],
        ];

        $correctInput2 = [
            'objectContainer' => [
                'string1' => '1',
            ],
        ];

        $correctInput3 = [
            'objectContainer' => [
                'string3' => '3',
                'string2' => 'something',
            ],
        ];

        $correctInput4 = [
            'objectContainer' => [
                'string3' => '3',
            ],
        ];

        $correctInput5 = [
            'objectContainer' => [
                'string1' => '1',
                'string3' => '3',
            ],
        ];

        $incorrectInput1 = [
            'objectContainer' => [
                'string1' => '3',
            ],
        ];

        $incorrectInput2 = [
            'objectContainer' => [
                'string3' => '1',
            ],
        ];


        $incorrectInput3 = [
            'objectContainer' => [
                'string2' => 'something',
            ],
        ];

        $error = new SchemaValidationError(
            '#/objectContainer',
            SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
            "Given value does not passed anyOf validation"
        );

        return
            [
                [$schema, $correctInput1, []],
                [$schema, $correctInput2, []],
                [$schema, $correctInput3, []],
                [$schema, $correctInput4, []],
                [$schema, $correctInput5, []],
                [$schema, $incorrectInput1, [$error]],
                [$schema, $incorrectInput2, [$error]],
                [$schema, $incorrectInput3, [$error]],
            ];
    }


    /**
     * @dataProvider all_of_provider
     *
     * @param array $schema
     * @param array $input
     * @param array $expectedErrors
     *
     * @throws BuildException
     */
    public function test_it_validate_all_of_value($schema, $input, $expectedErrors = [])
    {
        $property = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $property->fromArray($schema);

        $errors = $property->validate($input);

        $this->assertEquals($expectedErrors, $errors, print_r($errors, true));
    }

    /**
     * @return array
     */
    public function all_of_provider()
    {
        $schema = [
            'properties' => [
                'objectContainer' => [
                    'type'  => 'object',
                    'allOf' => [
                        [
                            'type'       => 'object',
                            'properties' => [
                                'string1' => ['type' => 'string', 'const' => '1', 'required' => true],
                                'string2' => ['type' => 'string'],
                            ],
                        ],
                        [
                            'type'       => 'object',
                            'properties' => [
                                'string3' => ['type' => 'string', 'const' => '3', 'required' => true],
                                'string2' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];


        $correctInput1 = [
            'objectContainer' => [
                'string1' => '1',
                'string3' => '3',
            ],
        ];

        $correctInput2 = [
            'objectContainer' => [
                'string1' => '1',
                'string2' => '2',
                'string3' => '3',
            ],
        ];

        $incorrectInput1 = [
            'objectContainer' => [
                'string1' => '1',
                'string2' => 'something',
            ],
        ];

        $errorsString3 = [
            new SchemaValidationError(
                '#/objectContainer',
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed allOf validation"
            ),
            new SchemaValidationError(
                '#/objectContainer/string3',
                SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                "Required property 'string3' is missing"
            ),
        ];

        $errorsString1 = [
            new SchemaValidationError(
                '#/objectContainer',
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed allOf validation"
            ),
            new SchemaValidationError(
                '#/objectContainer/string1',
                SchemaValidationError::REQUIRED_PROPERTY_MISSING,
                "Required property 'string1' is missing"
            ),
        ];

        $errorsConst1 = [
            new SchemaValidationError(
                '#/objectContainer',
                SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
                "Given value does not passed allOf validation"
            ),
            new SchemaValidationError(
                '#/objectContainer/string1',
                SchemaValidationError::NOT_EQUALS_TO_CONST,
                "Value '3' failed const constraint (1)"
            ),
        ];

        $incorrectInput2 = [
            'objectContainer' => [
                'string1' => '1',
            ],
        ];

        $incorrectInput3 = [
            'objectContainer' => [
                'string3' => '3',
                'string2' => 'something',
            ],
        ];

        $incorrectInput4 = [
            'objectContainer' => [
                'string3' => '3',
            ],
        ];


        $incorrectInput5 = [
            'objectContainer' => [
                'string1' => '3',
            ],
        ];

        $incorrectInput6 = [
            'objectContainer' => [
                'string3' => '1',
            ],
        ];


        $incorrectInput7 = [
            'objectContainer' => [
                'string2' => 'something',
            ],
        ];

        return
            [
                [$schema, $correctInput1, []],
                [$schema, $correctInput2, []],
                [$schema, $incorrectInput1, $errorsString3],
                [$schema, $incorrectInput2, $errorsString3],
                [$schema, $incorrectInput3, $errorsString1],
                [$schema, $incorrectInput4, $errorsString1],
                [$schema, $incorrectInput5, $errorsConst1],
                [$schema, $incorrectInput6, $errorsString1],
                [$schema, $incorrectInput7, $errorsString1],
            ];
    }


    /**
     * @dataProvider not_provider
     *
     * @param array $schema
     * @param array $input
     * @param array $expectedErrors
     *
     * @throws BuildException
     */
    public function test_it_validate_not_value($schema, $input, $expectedErrors = [])
    {
        $property = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $property->fromArray($schema);

        $errors = $property->validate($input);

        $this->assertEquals($expectedErrors, $errors, print_r($errors, true));
    }

    /**
     * @return array
     */
    public function not_provider()
    {
        $schema = [
            'properties' => [
                'objectContainer' => [
                    'type' => 'object',
                    'not'  => [
                        [
                            'type'       => 'object',
                            'properties' => [
                                'string1' => ['type' => 'string', 'const' => '1', 'required' => true],
                            ],
                        ],
                        [
                            'type'       => 'object',
                            'properties' => [
                                'string3' => ['type' => 'string', 'const' => '3', 'required' => true],
                            ],
                        ],
                    ],
                ],
            ],
        ];


        $correctInput1 = [
            'objectContainer' => [
                'string2' => 'something',
            ],
        ];
        $correctInput2 = [
            'objectContainer' => [
                'string1' => 'something',
            ],
        ];
        $correctInput3 = [
            'objectContainer' => [
                'string3' => 'something',
            ],
        ];
        $correctInput4 = [
            'objectContainer' => [
                'string1' => '3',
                'string3' => '1',
            ],
        ];
        $incorrectInput1 = [
            'objectContainer' => [
                'string1' => '1',
                'string3' => '3',
            ],
        ];
        $incorrectInput2 = [
            'objectContainer' => [
                'string1' => '1',
            ],
        ];
        $incorrectInput3 = [
            'objectContainer' => [
                'string3' => '3',
            ],
        ];

        $error = new SchemaValidationError(
            '#/objectContainer',
            SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION,
            "Given value does not passed not validation"
        );

        return
            [
                [$schema, $correctInput1, []],
                [$schema, $correctInput2, []],
                [$schema, $correctInput3, []],
                [$schema, $correctInput4, []],
                [$schema, $incorrectInput1, [$error]],
                [$schema, $incorrectInput2, [$error]],
                [$schema, $incorrectInput3, [$error]],
            ];
    }

}