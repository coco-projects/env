<?php

    namespace Coco\env\Exception;

class ParseException extends \ErrorException
{
    public function __construct($message, $line = null, $line_num = null)
    {
        $message = $this->createMessage($message, $line, $line_num);
        parent::__construct($message);
    }

    private function createMessage(string $message, $line, $line_num): string
    {
        if (!is_null($line)) {
            $message .= sprintf(" near %s", $line);
        }
        if (!is_null($line_num)) {
            $message .= sprintf(" at line %d", $line_num);
        }

        return $message;
    }
}
