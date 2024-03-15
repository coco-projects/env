<?php

    namespace Coco\env\Parser;

    use Coco\env\EnvParser;

abstract class AbstractParser
{
    protected EnvParser $parser;

    public function __construct(EnvParser $parser)
    {
        $this->parser = $parser;
    }
}
