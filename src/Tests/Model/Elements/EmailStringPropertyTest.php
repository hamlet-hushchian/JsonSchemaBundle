<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Elements;

use Hamlet\JsonSchemaBundle\Model\Elements\EmailStringProperty;

class EmailStringPropertyTest extends \PHPUnit_Framework_TestCase
{

    public function testDisplayingEmailStringProperty()
    {
        $property = new EmailStringProperty('test');
        $property->setMinLength(10)->setMaxLength(50)->setRequired(true);

        $expectedArray = [
            'type'      => 'string',
            'format'    => 'email',
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
        $property = new EmailStringProperty('test');
        $property->fromArray(
            [
                $property::MIN_LENGTH => 10,
                $property::MAX_LENGTH => 20,
            ]
        );

        $this->assertEquals(10, $property->getMinLength());
        $this->assertEquals(20, $property->getMaxLength());
        $this->assertEquals('email', $property->getFormat());
    }


    /**
     * @test
     */
    public function it_should_validate_input_value()
    {
        $property = new EmailStringProperty('test');
        $property->fromArray(
            [
                $property::MIN_LENGTH => 10,
                $property::MAX_LENGTH => 20,
            ]
        );

        $errors = $property->validate('great@email.com');

        $this->assertEmpty($errors);

        $errors = $property->validate('iamnotemail.com');
        $this->assertNotEmpty($errors);
    }
}