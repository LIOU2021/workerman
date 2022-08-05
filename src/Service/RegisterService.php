<?php

namespace Workerman\Service;

use Workerman\Connection\TcpConnection;

class RegisterService
{
    private static function addUid(string $uid, TcpConnection $connection)
    {
        if (isset($GLOBALS['users'][$uid])) {
            $connection->uid = 0;
            return false;
        } else {
            $GLOBALS['users'][$uid] = $connection->id;
            $connection->uid = $uid;
            echo "bindUid : success ! uid {$uid} \n";
            return true;
        }
    }

    public static function checkUid(TcpConnection $connection)
    {
        echo "checkUid : {$connection->uid} \n";
        if ($connection->uid != 0) {
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
                $uid = $json['uid'];
                if (!self::checkUid($connection)) {//uid尚無設置
                    $returnUid = self::addUid($uid, $connection);
                    if ($returnUid) {
                        $rep['type'] = 'bind';
                        $rep['uid'] = $uid;
                        $connection->send(json_encode(helpReturn(200, $rep)));
                        return true;
                    } else {
                        echo "bindUid : connection_id {$connection->id}, uid already exists \n";
                        $rep['type'] = 'bind';
                        $rep['uid'] = $uid;
                        $result = json_encode(helpReturn(408, $rep));
                        $connection->send($result);
                        echo $result . "\n";
                        return false;
                    }
                } else {
                    echo "bindUid : connection_id {$connection->id}, already bind ! \n";
                    $rep['type'] = 'bind';
                    $rep['uid'] = $uid;
                    $result = json_encode(helpReturn(409, $rep));
                    $connection->send($result);
                    echo $result . "\n";
                    return false;
                }
            } else {
                $connection->send(json_encode(helpReturn(406)));
            }
        } else {
            $connection->send(json_encode(helpReturn(407)));
        }
    }
}
