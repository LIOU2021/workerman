<?php

use Workerman\Config\App;

if (!function_exists('helpReturn')) {
    function helpReturn(int $status = 200, mixed $data = null)
    {
        $msg = App::generatorErrorCode($status);
        $res['status'] = $status;
        $res['msg'] = $msg;
        $res['data'] = $data;
        return $res;
    }
}
