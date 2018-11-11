<?php

namespace Hamlet\JsonSchemaBundle\Model\Util;


class ArrayUtils
{
    /**
     * @param array $arr
     * @param string $path
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function getArrayByPath($arr, $path, $defaultValue = null)
    {
        $path = explode('.', $path);
        foreach ($path as $item) {
            if (empty($arr[$item])) {
                return (empty($defaultValue) ? null : $defaultValue);
            }
            $arr = $arr[$item];
        }
        return $arr;
    }


    /**
     * @param array $arr
     * @param string $path
     * @param mixed $value
     * @param null|string $newKey
     */
    public static function setArrayByPath(&$arr, $path, $value, $newKey = null)
    {
        $path = explode('.', $path);
        $res = &$arr;
        foreach ($path as $item) {
            $res = &$res[$item];
        }
        if (empty($newKey)) {
            $res = $value;
        } else {
            $res[$newKey] = $value;
        }
        unset($res);
    }

    public static function removeArrayByPath(&$arr, $path)
    {
        $keys = explode('.', $path);
        $key = array_shift($keys);

        if (count($keys) == 0) {
            unset($arr[$key]);
        } else {
            static::removeArrayByPath($arr[$key], $keys);
        }
    }
}