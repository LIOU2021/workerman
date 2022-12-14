<?php

namespace Workerman\Service;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class MessageService
{
    public static function onConnect(Worker $worker, mixed $connectionId, TcpConnection $connection)
    {
        $res['type'] = 'onConnect';
        $res['yourself'] = false;
        $res['uid'] = $connection->uid;
        $res['connection_id'] = $connectionId;
        self::toAll($worker, $res);
    }

    public static function onClose(Worker $worker, TcpConnection $connection)
    {
        $uid = $connection->uid;
        $res['type'] = 'onClose';
        $res['uid'] = $uid;
        unset($GLOBALS['users'][$uid]);
        unset($GLOBALS['users2'][$connection->id]);
        $res['connection_id'] = $connection->id;

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

    public static function bindUid(Worker $worker, mixed $msg)
    {
        self::toAll($worker, $msg);
    }

    public static function onAutoReload(Worker $worker)
    {
        $replyMsg = [
            'type' => 'onAutoReload'
        ];
        self::toAll($worker, $replyMsg);
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
        $res['users2'] = $GLOBALS['users2'];
        $res['type'] = 'info';
        $rep = json_encode(helpReturn(200, $res));
        $connection->send($rep);
    }

    /**
     * client????????????????????????to??????key
     */
    private static function useTo(Worker $worker, TcpConnection $connection, $msg)
    {
        $json = json_decode($msg, true);
        if (isset($json['to'])) {
            // $connection->send("??????to??? : " . $json['msg']);

            switch ($json['to']) {
                case 'all':
                    $replyMsg['type'] = 'onAll';
                    $replyMsg['msg'] = $json['msg'];
                    $replyMsg['from_connectionId'] = $connection->id;
                    $replyMsg['from_uid'] = $connection->uid;
                    // $replyMsg = "??????id[{$connection->id}] ?????? ???: {$json['msg']}";
                    self::toAll($worker, $replyMsg);
                    break;
                case 'user':
                    if (isset($json['to_user'])) {
                        $connectId = explode("_", $json['to_user'])[1];
                        $reply = $json['msg'];
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
            //?????????????????????
            $res = json_encode(helpReturn(200, $msg));
            $conn->send($res);
        }
    }

    /**
     * push to user
     */
    private static function toUser(Worker $worker, TcpConnection $connection, $connectId, mixed $reply)
    {
        $toConnectId = $worker->id . "_" . $connectId;
        $res['type'] = 'onMessage';
        $res['from_connectionId'] = $connection->id;
        $res['from_Uid'] = $connection->uid;
        $res['to_connectionId'] = $toConnectId;
        $res['to_Uid'] = $GLOBALS['users2'][$toConnectId];
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
