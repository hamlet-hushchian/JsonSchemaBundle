<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Elements;


use Hamlet\JsonSchemaBundle\Model\Elements\StringProperty;


class StringPropertyTest extends \PHPUnit_Framework_TestCase
{

    public function testDisplayingStringProperty()
    {
        $property = new StringProperty('test');
        $property->setMinLength(10)->setMaxLength(50)->setDefault('test')->setRequired(true);

        $expectedArray = [
            'type'      => 'string',
            'default'   => 'test',
            'minLength' => 10,
            'maxLength' => 50,
        ];

        $this->assertEquals($expectedArray, $property->display());
    }


    /**
     * @test
     */
    public function it_should_create_from_array()
    {
        $property = new StringProperty('test');
        $property->fromArray([
            $property::MIN_LENGTH => 10,
            $property::MAX_LENGTH => 20,
        ]);

        $this->assertEquals(10, $property->getMinLength());
        $this->assertEquals(20, $property->getMaxLength());
    }


    /**
     * @test
     */
    public function it_should_validate_input_value()
    {
        $property = new StringProperty('test');
        $property->fromArray([
            $property::MIN_LENGTH => 10,
            $property::MAX_LENGTH => 20,
        ]);

        $errors = $property->validate('Normal String');
        $this->assertEmpty($errors);

        $errors = $property->validate('BadString');
        $this->assertNotEmpty($errors);
    }


    /**
     * @test
     */
    public function it_should_validae_pattern()
    {
        $property = new StringProperty('test');
        $property->fromArray([
            $property::PATTERN => '/^[a-z]+$/i',
        ]);

        $this->assertEmpty($property->validate('someString'));
        $this->assertNotEmpty($property->validate('some String'));
        $this->assertNotEmpty($property->validate('123'));
    }
}