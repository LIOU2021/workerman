<?php

namespace Workerman\Service;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class MessageService
{
    public static function send(Worker $worker, TcpConnection $connection, string $msg)
    {
        $json = json_decode($msg, true);
        if ($json) {
            self::readType($worker, $connection, $msg);
        } else {
            $connection->send("不是json格式 : " . $msg);
        }
    }

    private static function readType(Worker $worker, TcpConnection $connection, $msg)
    {
        $json = json_decode($msg,true);
        if (isset($json['type'])) {
            switch ($json['type']) {
                case 'message':
                    self::useTo($worker, $connection, $msg);
                    break;
                case 'info':
                    self::getInfo($connection,$worker);
                    break;
                default:
                    $connection->send("type not find ! : " . $msg);
                    break;
            }
        } else {
            $connection->send("type undefined ! : " . $msg);
        }
    }
    /**
     * return all worker info
     */
    private static function getInfo(TcpConnection $connection,Worker $worker)
    {
        $res['connectId']=$connection->id;
        $res['allConnect']=$worker->connections;
        $connection->send(json_encode($res));
    }

    /**
     * client端傳過來的訊息有to這個key
     */
    private static function useTo(Worker $worker, $connection, $msg)
    {
        $json = json_decode($msg,true);
        if (isset($json['to'])) {
            // $connection->send("有用to哦 : " . $json['msg']);

            switch ($json['to']) {
                case 'all':

                    //循环用户id，并发送信息
                    foreach ($worker->connections as $conn) {
                        //给用户发送信息
                        $conn->send("用户id[{$connection->id}] 廣播 说: {$json['msg']}");
                    }

                    // $connection->send("to type all : " . $json['msg']);
                    break;
                case 'user':
                    if (isset($json['to_user'])) {
                        $conectId = explode("_", $json['to_user'])[1];
                        $worker->connections[$conectId]->send("用户id[{$connection->id}] 私下 说: {$json['msg']}");
                    } else {
                        $connection->send("user connect id not find: " . $json['msg']);
                    }
                    break;
                default:
                    $connection->send("to type default : " . $json['msg']);
                    break;
            }
        } else {
            $connection->send("沒有to這個key : " . $msg);
        }
    }
}
