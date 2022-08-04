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
            self::useTo($worker,$json,$connection,$msg);
        } else {
            $connection->send("不是json格式 : " . $msg);
        }
    }

    /**
     * 獲得自己的連線ID
     */
    // public static function geyMyselfConnectId($connection,$msg){
    //     $connection->send("不是json格式 : " . $msg);
    // }

    /**
     * client端傳過來的訊息有to這個key
     */
    public static function useTo($worker,$json,$connection,$msg){
        if (isset($json['to'])) {
            // $connection->send("有用to哦 : " . $json['msg']);

            switch ($json['to']) {
                case 'all':
                    
                    //循环用户id，并发送信息
                    foreach($worker->connections as $conn)
                    {
                        //给用户发送信息
                        $conn->send("用户id[{$connection->id}] 廣播 说: {$json['msg']}");
                    }

                    // $connection->send("to type all : " . $json['msg']);
                    break;
                case 'user':
                    if(isset($json['to_user'])){
                        $conectId = explode("_",$json['to_user'])[1];
                        $worker->connections[$conectId]->send("用户id[{$connection->id}] 私下 说: {$json['msg']}");
                    }else{
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
