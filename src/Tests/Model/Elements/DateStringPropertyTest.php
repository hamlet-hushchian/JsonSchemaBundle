<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model\Elements;

use Hamlet\JsonSchemaBundle\Model\Elements\DateStringProperty;

class DateStringPropertyTest extends \PHPUnit_Framework_TestCase
{

    public function testDisplayingDateStringProperty()
    {
        $property = new DateStringProperty('test');
        $property->setMinValue('-1 year')->setMaxValue('+60 year');

        $expectedArray = [
            'type'     => 'string',
            'format'   => 'date',
            'minValue' => (new \DateTime('-1 year'))->format('Y-m-d'),
            'maxValue' => (new \DateTime('+60 year'))->format('Y-m-d'),
        ];

        $this->assertEquals($expectedArray, $property->display());
    }

    /**
     * @test
     * @expectedException \Hamlet\JsonSchemaBundle\Model\BuildException
     */
    public function it_should_fail_on_wrong_input()
    {
        $property = new DateStringProperty('test');
        $property->setMinValue('-abs');
    }

    /**
     * @test
     */
    public function it_should_create_from_array()
    {
        $property = new DateStringProperty('test');
        $property->fromArray(
            [
                $property::MIN_VALUE => '-1 year',
                $property::MAX_VALUE => '+60 year',
            ]
        );

        $this->assertEquals((new \DateTime('-1 year'))->format('Y-m-d'), $property->getMinValue());
        $this->assertEquals((new \DateTime('+60 year'))->format('Y-m-d'), $property->getMaxValue());
        $this->assertEquals('date', $property->getFormat());
    }


    /**
     * @test
     * @dataProvider data_provider_for_dates
     *
     * @param string $date
     * @param array $array
     * @param bool $isCorrect
     */
    public function it_should_validate_input_value($date, $array, $isCorrect)
    {
        $property = new DateStringProperty('test');
        $property->fromArray($array);

        $errors = $property->validate($date);
        $this->assertEquals($isCorrect, empty($errors), print_r($errors, true));
    }

    /**
     * @return array
     */
    public function data_provider_for_dates()
    {
        return [
            [
                (new \DateTime('now -1 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '-1 year', DateStringProperty::MAX_VALUE => '-20 year'],
                true,
            ],
            [
                (new \DateTime('now -1 year'))->format('Y-m-d'),
                [DateStringProperty::MAX_VALUE => '-20 year'],
                true,
            ],
            [
                (new \DateTime('now -19 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '-1 year',],
                true,
            ],
            [
                (new \DateTime('now -20 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '-1 year', DateStringProperty::MAX_VALUE => '-20 year'],
                true,
            ],
            [
                (new \DateTime('now -21 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '-1 year', DateStringProperty::MAX_VALUE => '-20 year'],
                false,
            ],
            [
                (new \DateTime('now +1 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '+1 year', DateStringProperty::MAX_VALUE => '+20 year'],
                true,
            ],
            [
                (new \DateTime('now +19 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '+1 year', DateStringProperty::MAX_VALUE => '+20 year'],
                true,
            ],
            [
                (new \DateTime('now +20 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '+1 year', DateStringProperty::MAX_VALUE => '+20 year'],
                true,
            ],
            [
                (new \DateTime('now +21 year'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '+1 year', DateStringProperty::MAX_VALUE => '+20 year'],
                false,
            ],
            [
                (new \DateTime('now +1 day'))->format('Y-m-d'),
                [DateStringProperty::MIN_VALUE => '+1 year', DateStringProperty::MAX_VALUE => '+20 year'],
                false,
            ],
        ];
    }
}