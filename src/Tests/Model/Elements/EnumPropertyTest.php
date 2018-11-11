<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Elements;

use Hamlet\JsonSchemaBundle\Model\Elements\EnumProperty;


class EnumPropertyTest extends \PHPUnit_Framework_TestCase
{

    public function testDisplayingEnumProperty()
    {
        $property = new EnumProperty('test');
        $property->setOptions(['a', 'b', 'c']);

        $expectedArray = [
            'type'    => 'enum',
            'options' => ['a', 'b', 'c'],
        ];

        $this->assertEquals($expectedArray, $property->display());
    }


    /**
     * @test
     */
    public function it_should_create_from_array()
    {
        $property = new EnumProperty('test');
        $property->fromArray(
            [
                $property::OPTIONS => ['a', 'b', 'c'],
            ]
        );

        $this->assertEquals(['a', 'b', 'c'], $property->getOptions());
    }


    /**
     * @test
     */
    public function it_should_validate_input_value()
    {
        $property = new EnumProperty('test');
        $property->fromArray(
            [
                $property::OPTIONS => ['a', 'b', 'c'],
            ]
        );

        $errors = $property->validate('a');
        $this->assertEmpty($errors);

        $errors = $property->validate('e');
        $this->assertNotEmpty($errors);
    }
}