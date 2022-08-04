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
            $res = json_encode(helpReturn(400, $msg));
            $connection->send($res);
        }
    }

    private static function readType(Worker $worker, TcpConnection $connection, $msg)
    {
        $json = json_decode($msg, true);
        if (isset($json['type'])) {
            switch ($json['type']) {
                case 'message':
                    self::useTo($worker, $connection, $msg);
                    break;
                case 'info':
                    self::getInfo($connection, $worker);
                    break;
                default:
                    $res = json_encode(helpReturn(401, $msg));
                    $connection->send($res);
                    break;
            }
        } else {
            $res = json_encode(helpReturn(402, $msg));
            $connection->send($res);
        }
    }
    /**
     * return all worker info
     */
    private static function getInfo(TcpConnection $connection, Worker $worker)
    {
        $res['connectId'] = $connection->id;
        $res['allConnect'] = $worker->connections;
        $rep = json_encode(helpReturn(200, $res));
        $connection->send($rep);
    }

    /**
     * client端傳過來的訊息有to這個key
     */
    private static function useTo(Worker $worker, $connection, $msg)
    {
        $json = json_decode($msg, true);
        if (isset($json['to'])) {
            // $connection->send("有用to哦 : " . $json['msg']);

            switch ($json['to']) {
                case 'all':

                    //循环用户id，并发送信息
                    foreach ($worker->connections as $conn) {
                        //给用户发送信息
                        $reply = "用户id[{$connection->id}] 廣播 说: {$json['msg']}";
                        $res = json_encode(helpReturn(200, $reply));
                        $conn->send($res);
                    }

                    // $connection->send("to type all : " . $json['msg']);
                    break;
                case 'user':
                    if (isset($json['to_user'])) {
                        $conectId = explode("_", $json['to_user'])[1];
                        $reply = "用户id[{$connection->id}] 私下 说: {$json['msg']}";
                        $res = json_encode(helpReturn(200, $reply));
                        if(isset($worker->connections[$conectId])){
                            $worker->connections[$conectId]->send($res);
                            $connection->send(json_encode(helpReturn()));
                        }else{
                            $res = json_encode(helpReturn(405, $conectId));
                            $connection->send($res);
                        }
                        
                    } else {
                        $res = json_encode(helpReturn(403, $json));
                        $connection->send($res);
                    }
                    break;
                default:
                    $res = json_encode(helpReturn(404, $json));
                    $connection->send($res);
                    break;
            }
        } else {
            $res = json_encode(helpReturn(402, $msg));
            $connection->send($res);
        }
    }
}
