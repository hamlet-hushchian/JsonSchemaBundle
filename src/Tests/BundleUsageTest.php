<?php

namespace Hamlet\JsonSchemaBundle\Tests\Model;

use Hamlet\JsonSchemaBundle\Model\Elements\ObjectProperty;
use Hamlet\JsonSchemaBundle\Model\Elements\StringProperty;
use Hamlet\JsonSchemaBundle\Model\SchemaValidationError;


class BundleUsageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider paymentContentProvider
     *
     * @param string $body
     * @param SchemaValidationError[]
     */
    public function it_should_return_informative_errors($body,$expectedErrors)
    {
        $creditCard = new ObjectProperty();
        $creditCard->addProperty((new StringProperty('type'))->setRequired(true)->setConstant('card'));
        $creditCard->addProperty((new StringProperty('usage'))->setRequired(true));
        $creditCard->addProperty((new StringProperty('number'))->setRequired(true));
        $creditCard->addProperty((new StringProperty('cardType'))->setRequired(true));
        $creditCard->addProperty((new StringProperty('cvv2'))->setRequired(true));
        $creditCard->addProperty((new StringProperty('expirationMonth'))->setRequired(true));
        $creditCard->addProperty((new StringProperty('expirationYear'))->setRequired(true));
        $creditCard->addProperty((new StringProperty('externalId')));
        $creditCard->addProperty((new StringProperty('paymentToken')));

        $sepaCard = new ObjectProperty();
        $sepaCard->addProperty((new StringProperty('type'))->setRequired(true)->setConstant('account'));
        $sepaCard->addProperty((new StringProperty('usage'))->setRequired(true));
        $sepaCard->addProperty((new StringProperty('bankAccount'))->setRequired(true));
        $sepaCard->addProperty(new StringProperty('bankCode'));

        $root = new ObjectProperty(ObjectProperty::ROOT_PROPERTY_NAME);
        $root->setContainerType($root::ONE_OF)
            ->addContainerItem($creditCard)
            ->addContainerItem($sepaCard);

        $errors = $root->validate($body);

        self::assertEquals($expectedErrors,$errors);
    }

    /**
     * @return array
     */
    public function paymentContentProvider()
    {
        $creditCard = [
            'type'            => 'card',
            'cvv2'            => '123',
            'expirationMonth' => '06',
            'expirationYear'  => '23',
            'externalId'      => '5e1f888b-584c-4ac2-9c0e-dd20ea6b808c',
        ];

        $creditCardErrorExpected = [
            new SchemaValidationError("#", SchemaValidationError::NOT_PASSED_ANY_CONTAINER_VALIDATION, "Given value does not passed oneOf validation"),
            new SchemaValidationError("#/usage", SchemaValidationError::REQUIRED_PROPERTY_MISSING, "Required property 'usage' is missing according to case 0 in OneOf validation"),
            new SchemaValidationError("#/number", SchemaValidationError::REQUIRED_PROPERTY_MISSING, "Required property 'number' is missing according to case 0 in OneOf validation"),
            new SchemaValidationError("#/cardType", SchemaValidationError::REQUIRED_PROPERTY_MISSING, "Required property 'cardType' is missing according to case 0 in OneOf validation"),
            new SchemaValidationError("#/usage", SchemaValidationError::REQUIRED_PROPERTY_MISSING, "Required property 'usage' is missing according to case 1 in OneOf validation"),
            new SchemaValidationError("#/bankAccount", SchemaValidationError::REQUIRED_PROPERTY_MISSING, "Required property 'bankAccount' is missing according to case 1 in OneOf validation"),
            new SchemaValidationError("#/type", SchemaValidationError::NOT_EQUALS_TO_CONST, "Value 'card' failed const constraint (account) according to case 1 in OneOf validation"),
        ];

        return [
            [$creditCard,$creditCardErrorExpected],
        ];
    }
}