<?php

namespace Workerman\Config;

use Generator;

class App
{
    /**
     * error code
     * 
     * @var array
     */
    static $error = [
        200 => 'success !',
        400 => 'json format error !',
        401 => 'type not find !',
        402 => 'type undefined !',
        403 => 'user connect id not find',
        404 => 'to type not find !',
        405 => 'connect id not find !',
        406 => 'uid undefined !',
        407 => 'type is not bind !',
        408 => 'uid already exists !',
        409 => 'uid already bind !',
    ];

    /**
     * app config
     * 
     * @var array
     */
    static $app = [
        "PORT" => 2000,
        "WORKER_COUNT" => 1,
        "MAX_REQUEST" => 1000,
        "MUST_UID" => false,
        "TIME_ZONE" => "Asia/Taipei",
        "Log_FILE" => "./log/workerman2.log",
    ];

    public static function generatorErrorCode(int $code)
    {
        $all = self::$error;
        if (isset($all[$code])) {
            return $all[$code];
        } else {
            return null;
        }
    }

    public static function generatorAppConfig(string $key, mixed $default = null)
    {
        $all = self::$app;
        if (isset($all[$key])) {
            return $all[$key];
        } else {
            return $default;
        }
    }
}
