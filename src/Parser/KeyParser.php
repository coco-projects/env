<?php

    namespace Coco\env\Parser;

    use Coco\env\Exception\ParseException;

class KeyParser extends AbstractParser
{
    public function parse($key): bool|string
    {
        $key = trim($key);
        if ($this->parser->getHelper()->startsWith('#', $key)) {
            return false;
        }
        if (!ctype_alnum(str_replace('_', '', $key)) || $this->parser->getHelper()->startsWithNumber($key)) {
            throw new ParseException(sprintf('Key can only contain alphanumeric and underscores and can not start with a number: %s', $key), $key, $this->parser->getLineNum());
        }

        return $key;
    }
}
