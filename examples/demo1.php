<?php

    use Coco\env\EnvParser;

    require '../vendor/autoload.php';

    EnvParser::loadEnvFile('.env');

    $arr = EnvParser::set('TEST899', '111111111');

    $arr = EnvParser::get('TEST8', '2222222');
    var_export($arr);
    echo PHP_EOL;

    $arr = EnvParser::get('TEST899', '33333333');
    var_export($arr);

    echo PHP_EOL;
    $arr = EnvParser::get('TEST855', '44444444');
    var_export($arr);

    echo PHP_EOL;
    $arr = EnvParser::getAll();
    var_export($arr);