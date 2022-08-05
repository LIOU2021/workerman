<?php

use Workerman\Config\App;

if (!function_exists('helpReturn')) {
    function helpReturn(int $status = 200, mixed $data = null)
    {
        $msg = App::generatorErrorCode($status);
        $res['status'] = $status;
        $res['msg'] = $msg;
        $res['data'] = $data;
        $res['datetime']=date("Y-m-d H:i:s");
        return $res;
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return App::generatorAppConfig($key, $default);
    }
}
