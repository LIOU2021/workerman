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
}
