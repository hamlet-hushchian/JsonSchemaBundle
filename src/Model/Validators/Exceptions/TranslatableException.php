<?php

namespace Hamlet\JsonSchemaBundle\Model\Validators\Exceptions;

abstract class TranslatableException extends \Exception
{
    abstract public function getExceptionID();

    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if ('' === $message) {
            $message = $this->short($this);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * The class name of an object, without the namespace
     * @param object|string $object
     * @return string
     */
    private function short($object)
    {
        $parts = explode('\\', $this->fqcn($object));

        return end($parts);
    }

    /**
     * Fully qualified class name of an object, without a leading backslash
     * @param object|string $object
     * @return string
     */
    private function fqcn($object)
    {
        if (is_string($object)) {
            return str_replace('.', '\\', $object);
        }

        if (is_object($object)) {
            return trim(get_class($object), '\\');
        }

        throw new \InvalidArgumentException(sprintf('Expected an object or a string, got %s', gettype($object)));
    }
}
