<?php

namespace Hamlet\JsonSchemaBundle\Model\Validators;

use League\JsonGuard\Constraint\DraftFour\Format\FormatExtensionInterface;
use League\JsonGuard\Validator;

class DateMinValueExtension implements FormatExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Validator $validator)
    {
        if (isset($validator->getSchema()->minValue)) {
            $now = new \DateTime('now');
            try {
                $date = new \DateTime($value);
            } catch (\Exception $e) {
                return \League\JsonGuard\error('Date does not in YYYY-MM-DD format', $validator);
            }
            if ($date->diff($now)->y < $validator->getSchema()->minValue) {
                return \League\JsonGuard\error('Date does not match minValue constraint', $validator);
            }
        }
        return null;
    }

}