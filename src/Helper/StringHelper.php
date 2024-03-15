<?php

    namespace Coco\env\Helper;

class StringHelper
{
    private static array $bool_variants = [
        'true',
        'false',
        'yes',
        'no',
    ];

    public function isBool($value): bool
    {
        return in_array(strtolower($value), self::$bool_variants);
    }

    public function isBoolInString($value, $quoted_string, $word_count): bool
    {
        return (is_bool($value)) && ($quoted_string || $word_count >= 2);
    }

    public function isNull($value): bool
    {
        return $value === 'null';
    }

    public function isNumber($value): bool
    {
        return is_numeric($value);
    }

    public function isString($value): bool
    {
        return $this->startsWith('\'', $value) || $this->startsWith('"', $value);
    }

    public function isVariableClone($value, $matches, $quoted_string): bool
    {
        return (count($matches[0]) === 1) && $value == $matches[0][0] && !$quoted_string;
    }

    public function startsWith($string, $line): bool
    {
        return $string === "" || strrpos($line, $string, -strlen($line)) !== false;
    }

    public function startsWithNumber($line): bool
    {
        return is_numeric(substr($line, 0, 1));
    }

    public function stripComments($value): string
    {
        $value = explode(" #", $value, 2);

        return trim($value[0]);
    }
}
