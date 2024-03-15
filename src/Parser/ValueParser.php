<?php

    namespace Coco\env\Parser;

    use Coco\env\Exception\ParseException;

class ValueParser extends AbstractParser
{
    const REGEX_QUOTE_DOUBLE_STRING = '"(?:[^\"\\\\]*(?:\\\\.)?)*\"';
    const REGEX_QUOTE_SINGLE_STRING = "'(?:[^'\\\\]*(?:\\\\.)?)*'";
    private static array   $value_types   = [
        'string',
        'bool',
        'number',
        'null',
    ];
    private static array   $character_map = [
        "\\n"  => "\n",
        "\\\"" => "\"",
        '\\\'' => "'",
        '\\t'  => "\t",
    ];
    private VariableParser $variable_parser;

    public function __construct($parser)
    {
        parent::__construct($parser);
        $this->variable_parser = new VariableParser($parser, []);
    }

    public function parse($value)
    {
        $value = trim($value);
        if ($this->parser->getHelper()->startsWith('#', $value)) {
            return null;
        }

        return $this->parseValue($value);
    }

    private function parseValue($value)
    {
        foreach (self::$value_types as $type) {
            $parsed_value = $value;
            if ($type !== 'string') {
                $parsed_value = $this->parser->getHelper()->stripComments($value);
            }
            [
                $is_function,
                $parse_function,
            ] = $this->fetchFunctionNames($type);
            if ($this->parser->getHelper()->$is_function($parsed_value)) {
                return $this->$parse_function($parsed_value);
            }
        }

        return (isset($parsed_value)) ? $this->parseUnquotedString($parsed_value) : $value;
    }

    private function fetchFunctionNames($type): array
    {
        $type = ucfirst($type);

        return [
            'is' . $type,
            'parse' . $type,
        ];
    }

    private function parseString($value)
    {
        $single = false;
        $regex  = self::REGEX_QUOTE_DOUBLE_STRING;
        $symbol = '"';
        if ($this->parser->getHelper()->startsWith('\'', $value)) {
            $single = true;
            $regex  = self::REGEX_QUOTE_SINGLE_STRING;
            $symbol = "'";
        }
        $matches = $this->fetchStringMatches($value, $regex, $symbol);
        $value   = $matches[0];
        if ($value !== '') {
            $value = substr($value, 1, strlen($value) - 2);
        }
        $value = strtr($value, self::$character_map);

        return ($single) ? $value : $this->variable_parser->parse($value, true);
    }

    private function fetchStringMatches($value, $regex, $symbol): array
    {
        if (!preg_match('/' . $regex . '/', $value, $matches)) {
            throw new ParseException(sprintf('Missing end %s quote', $symbol), $value, $this->parser->getLineNum());
        }

        return $matches;
    }

    private function parseNull($value): ?bool
    {
        return (is_null($value) || $value === "null") ? null : false;
    }

    private function parseUnquotedString($value): mixed
    {
        if ($value === "") {
            return null;
        }

        return $this->variable_parser->parse($value);
    }

    private function parseBool($value): bool
    {
        $value = strtolower($value);

        return $value === "true" || $value === "yes";
    }

    private function parseNumber($value): float|int
    {
        if (str_contains($value, '.')) {
            return (float)$value;
        }

        return (int)$value;
    }
}
