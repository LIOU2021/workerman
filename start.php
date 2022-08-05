<?php

use Workerman\Config\App;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Events\EventInterface;
use Workerman\Service\MessageService;
use Workerman\Service\RegisterService;
use Workerman\Timer;

require_once __DIR__ . '/vendor/autoload.php';

// 注意：这里与上个例子不同，使用的是websocket协议
$worker = new Worker("websocket://0.0.0.0:".env('port',2000));

// 设置实例的名称
$worker->name = 'MyWebsocketWorker';

// 每个进程最多执行1000个请求
define('MAX_REQUEST', env("MAX_REQUEST",1000));
//首次連線是否得註冊uid
define('MUST_UID', env("MUST_UID",false));
//存放uid && connectionId
$GLOBALS['users']=[];
date_default_timezone_set(env("TIME_ZONE","UTC"));

// 启动1个进程对外提供服务。由於是要使用在通訊用途，所以設定一個進程，避免進程間無法溝通問題。
$worker->count = env("WORKER_COUNT",1);

$worker->onWorkerStart = function ($worker) {
    echo "--------onWorkerStart--------\n";

    //DB類的初始撰寫注意地方
    // https://www.workerman.net/doc/workerman/faq/callback_methods.html#3%E3%80%81%E7%B1%BB%E6%96%B9%E6%B3%95%E4%BD%9C%E4%B8%BA%E5%9B%9E%E8%B0%83

    echo "worker->id={$worker->id}\n";

    if ($worker->id == 0) {
        echo "i am 0 process \n";
    } else {
    }

    //https://www.workerman.net/doc/workerman/tcp-connection/id.html#id
    //賦予每個coonnectID都為獨立的ID，所以加上進程id當作前綴．結果就是這裡的connect是每個連線user的獨立id．
    $worker->onConnect = function (TcpConnection $connection) use ($worker) {
        echo "--------onConnect--------\n";
        $connection->uid = 0;
        $connection->id = $worker->id . "_" . $connection->id;
        $msg = "online ! connection_id : " . $connection->id;
        echo $msg . "\n";
        MessageService::onConnect($worker, $connection->id);
        echo "-------------------\n";
    };

    //只在id編號為０的進程加上定時器
    if ($worker->id == 0) {
        // 定时，每5秒一次
        // Timer::add(5, function () use ($worker) {
        //     // 遍历当前进程所有的客户端连接，发送当前服务器的时间
        //     foreach ($worker->connections as $connection) {
        //         $connection->send("only process worker_id 0 : " . time());
        //     }
        // });

        //指定worker id 為 0的onConnect，如果這裡寫了，那麼後面主進程綁定的onConnect事件將會被覆蓋掉
        // $worker->onConnect = function (TcpConnection $connection) {
        //     echo "worker_ID 0 : connect ID : " . $connection->id . "\n";
        //     echo "worker_ID 0 : new connection from ip " . $connection->getRemoteIp() . "\n";
        // };
    }


    // 定时，每10秒一次
    // Timer::add(10, function () use ($worker) {
    //     // 遍历当前进程所有的客户端连接，发送当前服务器的时间
    //     foreach ($worker->connections as $connection) {
    //         $connection->send("to all people : " . time());
    //     }
    // });

    echo 'Pid is ' . posix_getpid() . "\n";
    echo "-------------------\n";
};

//因為在onWorker註冊了onConnect了，這裡的註冊事件將會被覆蓋
// $worker->onConnect = function (TcpConnection $connection) {
//     echo "--------onConnect--------\n";

//     echo "connect ID : " . $connection->id . "\n";
//     echo "new connection from ip " . $connection->getRemoteIp() . "\n";

//     echo "-------------------\n";
// };

// 当收到客户端发来的数据后返回hello $data给客户端
$worker->onMessage = function (TcpConnection $connection, $data) use ($worker) {
    echo "--------onMessage--------\n";

    // 向客户端发送hello $data
    echo 'onMessage : Pid is ' . posix_getpid() . "\n";

    $reply = 'hello connect ID : ' . $connection->id . "\nhello content : " . $data;
    echo $reply . "\n";

    // $connection->send($reply);

    $json = json_decode($data, true);

    if (MUST_UID) {
        //初次連線強迫得註冊uid的架構
        if (!RegisterService::checkUid($connection)) {
            RegisterService::bindUid($data, $connection,$worker);
        } else {
            echo "uid : {$connection->uid}\n";
            MessageService::onMessage($worker, $connection, $data);
        }
    } else {
        if (isset($json['type']) && $json['type'] == 'bind') {
            RegisterService::bindUid($data, $connection,$worker);
        } else {
            MessageService::onMessage($worker, $connection, $data);
        }
    }




    // 已经处理请求数
    static $request_count = 0;
    echo "request count : " . $request_count . "\n";

    // 如果请求数达到1000
    if (++$request_count >= MAX_REQUEST) {
        echo 'auto reload workerman' . "\n";
        /*
    * 退出当前进程，主进程会立刻重新启动一个全新进程补充上来
    * 从而完成进程重启
    */
        Worker::stopAll();
    }

    echo "-------------------\n";
};

$worker->onClose = function (TcpConnection $connection) use ($worker) {
    echo "--------onClose--------\n";

    $msg = "connect_id {$connection->id} logout !";
    echo $msg . "\n";
    MessageService::onClose($worker, $connection);
    echo "connection closed\n";
    echo "-------------------\n";
};

// 运行worker
Worker::runAll();
