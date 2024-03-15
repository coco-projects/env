<?php

    namespace Coco\env\Parser;

    use Coco\env\Exception\ParseException;

class VariableParser extends AbstractParser
{
    const REGEX_ENV_VARIABLE          = '\\${(.*?)}';
    const SYMBOL_ASSIGN_DEFAULT_VALUE = '=';
    const SYMBOL_DEFAULT_VALUE        = '-';
    private array $context;

    public function __construct($parser, array $context = [])
    {
        parent::__construct($parser);
        $this->context = $context;
    }

    public function parse($value, $quoted_string = false): mixed
    {
        $matches = $this->fetchVariableMatches($value);
        if (is_array($matches)) {
            if ($this->parser->getHelper()->isVariableClone($value, $matches, $quoted_string)) {
                return $this->fetchVariable($value, $matches[1][0], $matches, $quoted_string);
            }
            $value = $this->doReplacements($value, $matches, $quoted_string);
        }

        return $value;
    }

    private function fetchVariableMatches($value): array|bool
    {
        preg_match_all('/' . self::REGEX_ENV_VARIABLE . '/', $value, $matches);
        if (!is_array($matches) || empty($matches[0])) {
            return false;
        }

        return $matches;
    }

    private function fetchVariable($value, $variable_name, $matches, $quoted_string): mixed
    {
        if ($this->hasParameterExpansion($variable_name)) {
            $replacement = $this->fetchParameterExpansion($variable_name);
        } elseif ($this->hasVariable($variable_name)) {
            $replacement = $this->getVariable($variable_name);
        } else {
            throw new ParseException(sprintf('Variable has not been defined: %s', $variable_name), $value, $this->parser->getLineNum());
        }
        if ($this->parser->getHelper()->isBoolInString($replacement, $quoted_string, count($matches[0]))) {
            $replacement = ($replacement) ? 'true' : 'false';
        }

        return $replacement;
    }

    private function hasParameterExpansion($variable): bool
    {
        if ((str_contains($variable, self::SYMBOL_DEFAULT_VALUE)) || (str_contains($variable, self::SYMBOL_ASSIGN_DEFAULT_VALUE))) {
            return true;
        }

        return false;
    }

    private function fetchParameterExpansion($variable_name)
    {
        $parameter_type = $this->fetchParameterExpansionType($variable_name);
        [
            $parameter_symbol,
            $empty_flag,
        ] = $this->fetchParameterExpansionSymbol($variable_name, $parameter_type);
        [
            $variable,
            $default,
        ] = $this->splitVariableDefault($variable_name, $parameter_symbol);
        $value = $this->getVariable($variable);

        return $this->parseVariableParameter($variable, $default, $this->hasVariable($variable), $empty_flag && empty($value), $parameter_type);
    }

    private function fetchParameterExpansionType($variable_name): string
    {
        if (str_contains($variable_name, self::SYMBOL_ASSIGN_DEFAULT_VALUE)) {
            return 'assign_default_value';
        }

        return 'default_value'; // self::DEFAULT_VALUE_SYMBOL
    }

    private function fetchParameterExpansionSymbol($variable_name, $type): array
    {
        $class       = new \ReflectionClass($this);
        $symbol      = $class->getConstant('SYMBOL_' . strtoupper($type));
        $pos         = strpos($variable_name, $symbol);
        $check_empty = substr($variable_name, ($pos - 1), 1) === ":";
        if ($check_empty) {
            $symbol = sprintf(":%s", $symbol);
        }

        return [
            $symbol,
            $check_empty,
        ];
    }

    private function splitVariableDefault($variable_name, $parameter_symbol): array
    {
        $variable_default = explode($parameter_symbol, $variable_name, 2);
        if (count($variable_default) !== 2 || empty($variable_default[1])) {
            throw new ParseException('You must have valid parameter expansion syntax, eg. ${parameter:=word}', $variable_name, $this->parser->getLineNum());
        }

        return [
            trim($variable_default[0]),
            trim($variable_default[1]),
        ];
    }

    private function parseVariableParameter($variable, $default, $exists, $empty, $type)
    {
        if ($exists && !$empty) {
            return $this->getVariable($variable);
        }

        return $this->assignVariableParameterDefault($variable, $default, $empty, $type);
    }

    private function assignVariableParameterDefault($variable, $default, $empty, $type)
    {
        $default = $this->parser->getValueParser()->parse($default);
        if ($type === "assign_default_value" && $empty) {
            $this->parser->setLinesValue($variable, $default);
        }

        return $default;
    }

    private function hasVariable($variable): bool
    {
        if (array_key_exists($variable, $this->parser->getLines())) {
            return true;
        }
        if (array_key_exists($variable, $this->context)) {
            return true;
        }

        return false;
    }

    private function getVariable($variable)
    {
        if (array_key_exists($variable, $this->parser->getLines())) {
            return $this->parser->getLineValue($variable);
        }
        if (array_key_exists($variable, $this->context)) {
            return $this->context[$variable];
        }

        return null;
    }

    public function doReplacements($value, $matches, $quoted_string)
    {
        $replacements = [];
        for ($i = 0; $i <= (count($matches[0]) - 1); $i++) {
            $replacement                   = $this->fetchVariable($value, $matches[1][$i], $matches, $quoted_string);
            $replacements[$matches[0][$i]] = $replacement;
        }
        if (!empty($replacements)) {
            $value = strtr($value, $replacements);
        }

        return $value;
    }
}
