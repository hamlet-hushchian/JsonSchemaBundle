<?php

namespace Hamlet\JsonSchemaBundle\Model\Validators;

use League\JsonGuard\ConstraintInterface;
use League\JsonGuard\Validator;

class ConstExtension implements ConstraintInterface
{
    const KEYWORD = 'const';

    /**
     * {@inheritdoc}
     */
    public function validate($value, $parameter, Validator $validator)
    {
        if ($value != $parameter) {
            return \League\JsonGuard\error('Value does not match "const" constraint', $validator);
        }
        return null;
    }
}