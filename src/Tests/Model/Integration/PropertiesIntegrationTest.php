<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Integration;

use Hamlet\JsonSchemaBundle\Model\Elements\BooleanProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\EnumProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\NullProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\Property;
use Hamlet\JsonSchemaBundle\Model\Elements\ReferenceProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\StringProperty;



class PropertiesIntegrationTest extends \PHPUnit_Framework_TestCase
{

    public function testMergingBooleanProperty()
    {
        $expectedProperty = (new BooleanProperty('expected'))->setRequired(true)->setDefault(false);

        $initialProperty = (new BooleanProperty('expected'))->setRequired(false)->setDefault(true);

        $changedProperty = (new BooleanProperty('changed'))->setRequired(true)->setDefault(false);

        $actualProperty = $initialProperty->merge($changedProperty);

        $this->assertEquals($expectedProperty, $actualProperty);
    }

    public function testMergingReferenceProperty()
    {
        $expectedProperty = (new ReferenceProperty('expected'))->setReference('expected')
            ->setRequired(false)
            ->setDescription('desc');

        $initialProperty = (new ReferenceProperty('expected'))->setReference('not_expected')->setRequired(true);

        $changedProperty = (new ReferenceProperty('changed'))->setReference('expected')
            ->setRequired(false)
            ->setDescription('desc');

        $actualProperty = $initialProperty->merge($changedProperty);

        $this->assertEquals($expectedProperty, $actualProperty);
    }

    public function testMergingEnumProperty()
    {
        $expectedProperty = (new EnumProperty('expected'))
            ->setRequired(true)
            ->setOptions(['1', '2', '3', '4']);

        $initialProperty = (new EnumProperty('expected'))
            ->setRequired(false)
            ->setOptions(['1', '5', '7'])
            ->setDefault('5');

        $changedProperty = (new EnumProperty('expected'))
            ->setRequired(true)
            ->setOptions(['1', '2', '3', '4'])
            ->setMergeStrategy(Property::MERGE_STRATEGY_REPLACE);

        $actualProperty = $initialProperty->merge($changedProperty);

        $this->assertEquals($expectedProperty, $actualProperty);
    }


    public function testMergingStringProperty()
    {
        $expectedProperty = (new StringProperty('expected'))
            ->setRequired(true)
            ->setFormat('email')
            ->setMinLength(5)
            ->setMaxLength(10);

        $initialProperty = (new StringProperty('expected'))
            ->setRequired(false)
            ->setFormat('date')
            ->setMaxLength(10);

        $changedProperty = (new StringProperty('expected'))
            ->setRequired(true)
            ->setFormat('email')
            ->setMinLength(5);

        $actualProperty = $initialProperty->merge($changedProperty);

        $this->assertEquals($expectedProperty, $actualProperty);
    }

    public function testReturningOfNewPropertyInCaseOfMergeDifferentTypes()
    {

        $expectedProperty = (new StringProperty('expected'))->setFormat('email');

        $propertyToChange = (new BooleanProperty('expected'))->setRequired(true);

        $actualProperty = $propertyToChange->merge($expectedProperty);

        $this->assertEquals($expectedProperty, $actualProperty);
    }

    public function testBasicMergingWithRemoveStrategy()
    {
        $expectedProperty = (new ObjectProperty('expected'))->addProperty(
            new NullProperty('something')
        )->addProperty(
            new StringProperty('leave_me')
        );

        $propertyToChange = (new ObjectProperty('expected'))->addProperty(
            new StringProperty('something')
        )->addProperty(
            new StringProperty('leave_me')
        );

        $changedProperty = (new ObjectProperty('expected'))->addProperty(
            (new StringProperty('something'))->setMergeStrategy(Property::MERGE_STRATEGY_REMOVE)
        );

        $actualProperty = $propertyToChange->merge($changedProperty);

        $this->assertEquals($expectedProperty, $actualProperty);
    }

    public function testNestedMergingWithRemoveStrategy()
    {
        $expectedProperty = (new ObjectProperty('expected'))->addProperty(
            (new ObjectProperty('nested'))->addProperty(
                new NullProperty('something')
            )
        )->addProperty(
            new StringProperty('leave_me')
        );

        $propertyToChange = (new ObjectProperty('expected'))->addProperty(
            (new ObjectProperty('nested'))->addProperty(
                new StringProperty('something')
            )
        )->addProperty(
            new StringProperty('leave_me')
        );

        $changedProperty = (new ObjectProperty('expected'))
            ->addProperty(
                (new ObjectProperty('nested'))->addProperty(
                    (new StringProperty('something'))->setMergeStrategy(Property::MERGE_STRATEGY_REMOVE)
                )
            );

        $actualProperty = $propertyToChange->merge($changedProperty);

        $this->assertEquals($expectedProperty, $actualProperty);
    }


    public function testMergingObjects()
    {
        $expectedProperty = (new ObjectProperty('payment'))
            ->addProperty(
                (new StringProperty('type'))->setRequired(true)->setReadOnlyValue('card')
            )
            ->addProperty(
                (new StringProperty('number'))->setRequired(true)->setMaxLength(16)
            )
            ->addProperty(
                (new StringProperty('holder'))->setRequired(true)
            );

        $propertyToChange = (new ObjectProperty('payment'))
            ->addProperty(
                (new StringProperty('type'))->setRequired(true)->setReadOnlyValue('account')
            )
            ->addProperty(
                (new StringProperty('number'))->setFormat('accountNumber')->setRequired(false)
            )
            ->addProperty(
                (new StringProperty('bankName'))->setRequired(true)
            );

        $changedProperty = (new ObjectProperty('payment'))
            ->addProperty(
                (new StringProperty('type'))->setReadOnlyValue('card')
            )
            ->addProperty(
                (new StringProperty('number'))->setMaxLength(16)->setRequired(true)->setMergeStrategy(
                    Property::MERGE_STRATEGY_REPLACE
                )
            )
            ->addProperty(
                (new StringProperty('bankName'))->setMergeStrategy(Property::MERGE_STRATEGY_REMOVE)
            )
            ->addProperty(
                (new StringProperty('holder'))->setRequired(true)
            );

        $actualProperty = $propertyToChange->merge($changedProperty);

        $this->assertEquals($expectedProperty->display(), $actualProperty->display());
    }


    public function testGeneratingDependencies()
    {
        $property = (new ObjectProperty('root'))
            ->addProperty((new StringProperty('child'))->addDependency('parent'))
            ->addProperty((new BooleanProperty('parent')));

        $expectedResult = [
            'type'         => 'object',
            'properties'   => [
                'child'  => ['type' => 'string'],
                'parent' => ['type' => 'boolean'],
            ],
            'dependencies' => [
                'parent' => ['child'],
            ],
        ];

        $this->assertEquals($expectedResult, $property->display());
    }

    public function testGeneratingNestedDependencies()
    {
        $property = (new ObjectProperty('root'))
            ->addProperty((new StringProperty('child'))->addDependency('parent'))
            ->addProperty((new BooleanProperty('parent')))
            ->addProperty(
                (new ObjectProperty('nested'))
                    ->addProperty((new StringProperty('nestedChild'))->addDependency('nestedParent'))
                    ->addProperty((new BooleanProperty('nestedParent')))
            );

        $expectedResult = [
            'type'         => 'object',
            'properties'   => [
                'child'  => ['type' => 'string'],
                'parent' => ['type' => 'boolean'],
                'nested' => [
                    'type'         => 'object',
                    'properties'   => [
                        'nestedChild'  => ['type' => 'string'],
                        'nestedParent' => ['type' => 'boolean'],
                    ],
                    'dependencies' => ['nestedParent' => ['nestedChild']],
                ],
            ],
            'dependencies' => [
                'parent' => ['child'],
            ],
        ];

        $this->assertEquals($expectedResult, $property->display());
    }

    public function testParentFunctionality()
    {
        $parentProperty = (new ObjectProperty('parent'));
        $childObject = (new ObjectProperty('childObject'));
        $childProperty = (new StringProperty('child'));

        $parentProperty->addProperty(
            ($childObject->addProperty($childProperty))
        );

        $this->assertEquals('parent/childObject/child', $childProperty->getFullPath());
        $this->assertEquals('parent/childObject', $childObject->getFullPath());
    }
}