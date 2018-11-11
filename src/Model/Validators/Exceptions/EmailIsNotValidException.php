<?php

namespace Hamlet\JsonSchemaBundle\Model\Validators\Exceptions;

final class EmailIsNotValidException extends TranslatableException
{
    /** @var string */
    private $businessExceptionID = 'register.elements.email.error';

    /**
     * {@inheritdoc}
     */
    public function getExceptionID()
    {
        return $this->businessExceptionID;
    }
}
