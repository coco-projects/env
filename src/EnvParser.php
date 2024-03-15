<?php

    namespace Coco\env;

    use Coco\env\Exception\ParseException;
    use Coco\env\Helper\StringHelper;
    use Coco\env\Parser\ValueParser;
    use Coco\env\Parser\KeyParser;

class EnvParser
{
    protected static ?EnvParser $ins   = null;
    protected KeyParser         $keyParser;
    protected int               $lineNum;
    protected int               $content;
    protected array             $lines = [];
    protected StringHelper      $helper;
    protected ValueParser       $valueParser;

    protected function __construct()
    {
        $this->keyParser   = new KeyParser($this);
        $this->valueParser = new ValueParser($this);
        $this->helper      = new StringHelper();
    }

    public static function loadEnvFile(string $file): void
    {
        if (!is_file($file)) {
            throw new ParseException(sprintf('.env file not exists: %s ', $file));
        }
        $content = file_get_contents($file);
        static::initIns()->doParse($content);
    }

    public static function getAll(): array
    {
        return static::initIns()->lines;
    }

    public static function get($keyName, $default = null): mixed
    {
        $this_ = static::initIns();
        $value = array_key_exists($keyName, $this_->lines) ? $this_->lines[$keyName] : null;
        if (!$value) {
            $value = $default;
        }

        return $value;
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    public function setLinesValue($keyName, $value): void
    {
        $this->lines[$keyName] = $value;
    }

    public function getLineValue($keyName): mixed
    {
        return $this->lines[$keyName] ?? null;
    }

    public static function set($keyName, $value): mixed
    {
        $this_                  = static::initIns();
        $this_->lines[$keyName] = $value;

        return $value;
    }

    public function getLineNum(): int
    {
        return $this->lineNum;
    }

    public function getValueParser(): ValueParser
    {
        return $this->valueParser;
    }

    public function getKeyParser(): KeyParser
    {
        return $this->keyParser;
    }

    public function getHelper(): StringHelper
    {
        return $this->helper;
    }

    protected static function initIns(): static
    {
        if (!static::$ins) {
            static::$ins = new static();
        }

        return static::$ins;
    }

    protected function doParse(string $content): array
    {
        $raw_lines = array_filter($this->makeLines($content), 'strlen');
        if (empty($raw_lines)) {
            return [];
        }

        return $this->parseContent($raw_lines);
    }

    protected function makeLines(string $content): array
    {
        return explode("\n", str_replace([
            "\r\n",
            "\n\r",
            "\r",
        ], "\n", $content));
    }

    protected function parseContent(array $raw_lines): array
    {
        $this->lines   = [];
        $this->lineNum = 0;
        foreach ($raw_lines as $raw_line) {
            $this->lineNum++;
            $line = trim($raw_line);
            if ($this->helper->startsWith('#', $line) || !$line) {
                continue;
            }
            $this->parseLine($raw_line);
        }

        return $this->lines;
    }

    protected function parseLine($raw_line): void
    {
        $raw_line = $this->parseExport($raw_line);
        [
            $key,
            $value,
        ] = $this->parseKeyValue($raw_line);
        $key = $this->keyParser->parse($key);
        if (!is_string($key)) {
            return;
        }
        $this->lines[$key] = $this->valueParser->parse($value);
    }

    protected function parseExport($raw_line): string
    {
        $line = trim($raw_line);
        if ($this->helper->startsWith("export", $line)) {
            $export_line = explode("export", $raw_line, 2);
            if (count($export_line) !== 2 || empty($export_line[1])) {
                throw new ParseException('You must have a export key = value', $raw_line, $this->lineNum);
            }
            $line = trim($export_line[1]);
        }

        return $line;
    }

    private function parseKeyValue($raw_line): array
    {
        $key_value = explode("=", $raw_line, 2);
        if (count($key_value) !== 2) {
            throw new ParseException('You must have a key = value', $raw_line, $this->lineNum);
        }

        return $key_value;
    }
}
