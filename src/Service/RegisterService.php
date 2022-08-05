<?php

namespace Workerman\Service;

use Workerman\Connection\TcpConnection;

class RegisterService
{
    private static function addUid(string $uid, TcpConnection $connection)
    {
        echo "bindUid : success ! uid {$uid} \n";
        $connection->uid = $uid;
    }

    public static function checkUid(TcpConnection $connection)
    {
        if ($connection->uid) {
            return true;
        } else {
            return false;
        }
    }

    public static function bindUid(string $msg, TcpConnection $connection)
    {
        $json = json_decode($msg, true);
        if (isset($json['type']) && $json['type'] == 'bind') {
            if (isset($json['uid'])) {
                if (!self::checkUid($connection)) {
                    $uid = $json['uid'];
                    self::addUid($uid, $connection);
                    $rep['type'] = 'bind';
                    $connection->send(json_encode(helpReturn(200, $rep)));
                } else {
                    echo "bindUid : connection_id {$connection->id}, uid already exists \n";
                }
            } else {
                $connection->send(json_encode(helpReturn(406)));
            }
        } else {
            $connection->send(json_encode(helpReturn(407)));
        }
    }
}
