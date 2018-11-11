<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model;

use Hamlet\JsonSchemaBundle\Model\Config\ArrayConfig;
use Hamlet\JsonSchemaBundle\Model\JsonSchemaBuilder;


class JsonSchemaBuilderTest extends \PHPUnit_Framework_TestCase
{

    public function testBasicGenerating()
    {
        $builder = new JsonSchemaBuilder();

        $baseConfig = new ArrayConfig(
            [
                'properties'  => [
                    'shouldBeFilled'     => [
                        'type'    => 'string',
                        'default' => '{{valueToFill}}',
                    ],
                    'shouldBeInRequired' => [
                        'type'     => 'string',
                        'required' => true,
                    ],
                    'shouldBeRemoved'    => [
                        'type'     => 'boolean',
                        'required' => true,
                    ],
                    'shouldBeInlined'    => [
                        'type' => 'reference',
                        'ref'  => 'inlined',
                    ],
                    'shouldBeReference'  => [
                        'type' => 'reference',
                        'ref'  => 'defined',
                    ],
                ],
                'variables'   => [
                    'valueToFill' => '123',
                ],
                'customData'  => [
                    'testOption' => 'shouldBeReplaced',
                    'this'       => 'untouched',
                ],
                'definitions' => [
                    'inlined' => [
                        'type'      => 'string',
                        'inline'    => true,
                        'maxLength' => 10,
                    ],
                    'defined' => [
                        'type' => 'string',
                    ],
                ],
            ]
        );

        $specificConfig = new ArrayConfig(
            [
                'properties'  => [
                    'shouldBeRemoved' => [
                        'type'          => 'string',
                        'mergeStrategy' => 'remove',
                    ],
                    'parent'          => [
                        'type'          => 'boolean',
                        'default'       => false,
                        'mergeStrategy' => 'replace',
                    ],
                    'child'           => [
                        'type'       => 'object',
                        'dependsOn'  => 'parent',
                        'properties' => [
                            'nestedProperty'   => [
                                'type'     => 'string',
                                'required' => true,
                            ],
                            'nestedDefinition' => [
                                'type' => 'boolean',
                            ],
                        ],
                    ],
                ],
                'variables'   => [
                    'valueToFill' => '321',
                ],
                'customData'  => [
                    'testOption' => 'replaced',
                ],
                'definitions' => [
                    'nestedDefinition' => [
                        'type'   => 'boolean',
                        'inline' => true,
                    ],
                ],
            ]
        );

        $builder->addConfig($baseConfig)->addConfig($specificConfig);

        $builder->addCustomData(['dynamicValue' => 'works']);

        $actualResult = $builder->generate();


        $expectedResult = [
            'type'         => 'object',
            'properties'   => [
                'shouldBeFilled'     => ['type' => 'string', 'default' => '321'],
                'shouldBeInRequired' => ['type' => 'string'],
                'parent'             => ['type' => 'boolean', 'default' => false, 'mergeStrategy' => 'replace'],
                'child'              => [
                    'type'       => 'object',
                    'properties' => [
                        'nestedProperty'   => [
                            'type' => 'string',
                        ],
                        'nestedDefinition' => [
                            'type' => 'boolean',
                        ],
                    ],
                    'required'   => ['nestedProperty'],
                ],
                'shouldBeInlined'    => ['type' => 'string', 'maxLength' => 10],
                'shouldBeReference'  => ['type' => 'ref', '$ref' => '#/definitions/defined'],
            ],
            'required'     => [
                'shouldBeInRequired',
            ],
            'dependencies' => [
                'parent' => ['child'],
            ],
            'customData'   => [
                'testOption'   => 'replaced',
                'this'         => 'untouched',
                'dynamicValue' => 'works',
            ],
            'definitions'  => [
                'defined'          => [
                    'type' => 'string',
                ],
            ],

        ];
        $this->assertEquals($expectedResult, $actualResult);
    }


    public function testInlineDefinitions()
    {
        $builder = new JsonSchemaBuilder();

        $config = new ArrayConfig(
            [
                'properties'  => [
                    'shouldBeChanged' => [
                        'type'    => 'reference',
                        'ref'     => 'inlineDefinition',
                        'const'   => 'shouldNotBeChanged',
                    ],
                ],
                'definitions' => [
                    'inlineDefinition' => [
                        'type'      => 'string',
                        'minLength' => 5,
                        'const'     => 'itShouldNotBeInResult',
                        'inline'    => true,
                    ],
                ],
            ]
        );
        $builder->addConfig($config);

        $actualResult = $builder->generate();

        $expectedResult = [
            'type'       => 'object',
            'properties' => [
                'shouldBeChanged' => [
                    'type'      => 'string',
                    'minLength' => 5,
                    'const'     => 'shouldNotBeChanged',
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $actualResult);
    }
}