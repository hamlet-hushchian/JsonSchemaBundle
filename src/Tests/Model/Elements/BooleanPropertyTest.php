<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Elements;

use Hamlet\JsonSchemaBundle\Model\Elements\BooleanProperty;

class BooleanPropertyTest extends \PHPUnit_Framework_TestCase
{

    public function testDisplayingBooleanProperty()
    {
        $property = new BooleanProperty('test');
        $property->setDefault(false)->setRequired(true);

        $expectedArray = ['type' => 'boolean', 'default' => false];

        $this->assertEquals($expectedArray, $property->display());
    }

    /**
     * @dataProvider validating_data_provider
     *
     * @param mixed $value
     * @param boolean $emptyErrors
     */
    public function testValidatingBooleanProperty($value, $emptyErrors)
    {
        $property = new BooleanProperty('test');

        $errors = $property->validate($value);

        $this->assertEquals($emptyErrors, empty($errors));
    }

    public function validating_data_provider()
    {
        return [
            [true, true],
            [false, true],
            ['string', false]
        ];
    }
}