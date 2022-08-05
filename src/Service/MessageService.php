<?php

namespace Workerman\Service;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class MessageService
{
    public static function onConnect(Worker $worker, mixed $connectionId)
    {
        $res['type'] = 'onConnect';
        $res['connection_id'] = $connectionId;
        self::toAll($worker, $res);
    }

    public static function onClose(Worker $worker, mixed $connectionId)
    {
        $res['type'] = 'onClose';
        $res['connection_id'] = $connectionId;
        self::toAll($worker, $res);
    }

    public static function onMessage(Worker $worker, TcpConnection $connection, string $msg)
    {
        $json = json_decode($msg, true);
        if ($json) {
            self::readType($worker, $connection, $msg);
        } else {
            $res = json_encode(helpReturn(400, $msg));
            $connection->send($res);
        }
    }

    public static function bindUid(Worker $worker,mixed $msg)
    {
        self::toAll($worker, $msg);
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
        $res['users'] = $GLOBALS['users'];
        $res['type'] = 'info';
        $rep = json_encode(helpReturn(200, $res));
        $connection->send($rep);
    }

    /**
     * client端傳過來的訊息有to這個key
     */
    private static function useTo(Worker $worker, TcpConnection $connection, $msg)
    {
        $json = json_decode($msg, true);
        if (isset($json['to'])) {
            // $connection->send("有用to哦 : " . $json['msg']);

            switch ($json['to']) {
                case 'all':

                    $replyMsg = "用户id[{$connection->id}] 廣播 说: {$json['msg']}";
                    self::toAll($worker, $replyMsg);
                    break;
                case 'user':
                    if (isset($json['to_user'])) {
                        $connectId = explode("_", $json['to_user'])[1];
                        $reply = "用户id[{$connection->id}] 私下 说: {$json['msg']}";
                        self::toUser($worker, $connection, $connectId, $reply);
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

    /**
     * push all people message
     */
    private static function toAll(Worker $worker, mixed $msg)
    {
        foreach ($worker->connections as $conn) {
            //给用户发送信息
            $res = json_encode(helpReturn(200, $msg));
            $conn->send($res);
        }
    }

    /**
     * push to user
     */
    private static function toUser(Worker $worker, TcpConnection $connection, $connectId, mixed $reply)
    {
        $res['type'] = 'onMessage';
        $res['from'] = $connection->id;
        $res['to'] = $connectId;
        $res['msg'] = $reply;


        if (isset($worker->connections[$connectId])) {
            $res['sender'] = false;
            $rep = json_encode(helpReturn(200, $res));
            $worker->connections[$connectId]->send($rep);
            // $connection->send(json_encode(helpReturn()));
            $res['sender'] = true;
            $rep = json_encode(helpReturn(200, $res));
            $connection->send($rep);
        } else {
            $rep = json_encode(helpReturn(405, $res));
            $connection->send($rep);
        }
    }
}
