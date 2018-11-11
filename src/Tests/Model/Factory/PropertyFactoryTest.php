<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Factory;

use Hamlet\JsonSchemaBundle\Model\Elements\BooleanProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\EmailStringProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\EnumProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\Property;
use Hamlet\JsonSchemaBundle\Model\Elements\StringProperty;
use Hamlet\JsonSchemaBundle\Model\Factory\PropertyFactory;


// todo: test all possible types of properties and definitions
class PropertyFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testCreatingFromArray()
    {
        $array = [
            'properties' => [
                'first'  => [
                    'type'      => 'string',
                    'required'  => true,
                    'minLength' => 15,
                    'format'    => 'email',
                ],
                'second' => [
                    'type'       => 'object',
                    'required'   => true,
                    'properties' => [
                        'nestedFirst'  => [
                            'type'      => 'string',
                            'default'   => 'nested',
                            'maxLength' => 25,
                        ],
                        'nestedSecond' => [
                            'type'     => 'boolean',
                            'required' => true,
                        ],
                        'nestedEnum'   => [
                            'type'      => 'enum',
                            'options'   => ['1', '2', '3'],
                            'dependsOn' => 'nestedSecond',
                        ],
                    ],
                ],
            ],
            'variables'  => [
                'shouldBe' => 'skipped',
            ],
        ];

        $firstProperty = (new EmailStringProperty('first'))->setRequired(true)->setMinLength(15);

        $nestedFirstProperty = (new StringProperty('nestedFirst'))->setDefault('nested')->setMaxLength(25);
        $nestedSecondProperty = (new BooleanProperty('nestedSecond'))->setRequired(true);
        $nestedEnum = (new EnumProperty('nestedEnum'))->setOptions(['1', '2', '3'])->addDependency('nestedSecond');

        $secondProperty = (new ObjectProperty('second'))->setRequired(true)
            ->addProperty($nestedFirstProperty)
            ->addProperty($nestedSecondProperty)
            ->addProperty($nestedEnum);
        $expectedProperty = (new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME))->addProperty($firstProperty)
            ->addProperty($secondProperty);

        $actualProperty = PropertyFactory::get()->createFromArray($array);

        $this->assertEquals($expectedProperty, $actualProperty);
    }
}