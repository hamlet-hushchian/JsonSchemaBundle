<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Integration;

use Hamlet\JsonSchemaBundle\Model\BuildException;
use Hamlet\JsonSchemaBundle\Model\Config\ArrayConfig;
use Hamlet\JsonSchemaBundle\Model\JsonSchemaBuilder;



class VariablesIntegrationTest extends \PHPUnit_Framework_TestCase
{

    public function testFillingVariablesFromConfig()
    {
        $builder = new JsonSchemaBuilder();

        $config = new ArrayConfig(
            [
                'properties' => [
                    'shouldBeFilled' => [
                        'type'    => 'string',
                        'default' => '{{valueToFill}}',
                    ],
                ],
                'variables'  => [
                    'valueToFill' => '321',
                ],
            ]
        );

        $builder->addConfig($config);

        $expectedResult = [
            'type'       => 'object',
            'properties' => [
                'shouldBeFilled' => ['type' => 'string', 'default' => '321'],
            ],
        ];

        $this->assertEquals($expectedResult, $builder->generate());
    }

    public function testOverridingVariablesFromConfig()
    {
        $builder = new JsonSchemaBuilder();

        $config = new ArrayConfig(
            [
                'properties' => [
                    'shouldBeFilled' => [
                        'type'    => 'string',
                        'default' => '{{valueToFill}}',
                    ],
                ],
                'variables'  => [
                    'valueToFill' => '321',
                ],
            ]
        );

        $specificConfig = new ArrayConfig(
            [
                'variables' => [
                    'valueToFill' => '123',
                ],
            ]
        );
        $builder->addConfig($config)->addConfig($specificConfig);

        $expectedResult = [
            'type'       => 'object',
            'properties' => [
                'shouldBeFilled' => ['type' => 'string', 'default' => '123'],
            ],
        ];

        $this->assertEquals($expectedResult, $builder->generate());
    }

    /**
     * @expectedException \Hamlet\JsonSchemaBundle\Model\BuildException
     */
    public function testThrowingExceptionIfNoVariable()
    {
        $builder = new JsonSchemaBuilder();

        $config = new ArrayConfig(
            [
                'properties' => [
                    'shouldBeFilled' => [
                        'type'    => 'string',
                        'default' => '{{valueToFill}}',
                    ],
                ],
            ]
        );

        $builder->addConfig($config);
        $builder->generate();
    }

    public function testFillingNestedVariables()
    {
        $builder = new JsonSchemaBuilder();

        $config = new ArrayConfig(
            [
                'properties' => [
                    'first'  => [
                        'type' => 'string',
                    ],
                    'second' => [
                        'type'       => 'object',
                        'properties' => [
                            'nestedEnum' => [
                                'type'    => 'enum',
                                'options' => ['1', '2', '3'],
                                'default' => '{{shouldBe}}',
                            ],
                        ],
                    ],
                ],
                'variables'  => [
                    'shouldBe' => '1',
                ],
            ]
        );

        $builder->addConfig($config);
        $actualResult = $builder->generate();

        $expectedResult = [
            'type'       => 'object',
            'properties' => [
                'first'  => ['type' => 'string'],
                'second' => [
                    'type'       => 'object',
                    'properties' => [
                        'nestedEnum' => [
                            'type'    => 'enum',
                            'options' => ['1', '2', '3'],
                            'default' => '1',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testFillingFewVariables()
    {
        $builder = new JsonSchemaBuilder();

        $config = new ArrayConfig(
            [
                'properties' => [
                    'first'  => [
                        'type'    => 'string',
                        'default' => '{{variableForString}}',
                    ],
                    'second' => [
                        'type'       => 'object',
                        'properties' => [
                            'nestedEnum'    => [
                                'type'    => 'enum',
                                'options' => ['1', '2', '3'],
                                'default' => '{{variableForEnum}}',
                            ],
                            'nestedBoolean' => [
                                'type'  => 'boolean',
                                'const' => '{{variableForBoolean}}',
                            ],
                        ],
                    ],
                ],
                'variables'  => [
                    'variableForEnum'    => '1',
                    'variableForString'  => 'i_am_string',
                    'variableForBoolean' => true,
                ],
            ]
        );

        $builder->addConfig($config);
        $actualResult = $builder->generate();

        $expectedResult = [
            'type'       => 'object',
            'properties' => [
                'first'  => ['type' => 'string', 'default' => 'i_am_string'],
                'second' => [
                    'type'       => 'object',
                    'properties' => [
                        'nestedEnum'    => [
                            'type'    => 'enum',
                            'options' => ['1', '2', '3'],
                            'default' => '1',
                        ],
                        'nestedBoolean' => [
                            'type'  => 'boolean',
                            'const' => true,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $actualResult);
    }


    public function testVariablesWithBrackets()
    {
        // we need this test to be sure, that variable with brackets will not lead to infinite recursion
        $builder = new JsonSchemaBuilder();

        $config = new ArrayConfig(
            [
                'properties' => [
                    'shouldBeFilled' => [
                        'type'    => 'string',
                        'default' => '{{valueToFill}}',
                    ],
                ],
                'variables'  => [
                    'valueToFill' => '{321}',
                ],
            ]
        );

        $builder->addConfig($config);

        $expectedResult = [
            'type'       => 'object',
            'properties' => [
                'shouldBeFilled' => ['type' => 'string', 'default' => '{321}'],
            ],
        ];

        $this->assertEquals($expectedResult, $builder->generate());
    }

}