<?php

class Test
{
    public $index = 0;
}

class Server
{
    private $serv;

    public function __construct(){
        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'worker_num' => 8,
            'daemonize' => false,
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'task_worker_num' => 8
        ));
        $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect',array($this,'onConnect'));
        $this->serv->on('Receive',array($this,'onReceive'));
        $this->serv->on('Close',array($this,'onClose'));
        //bind callback
        $this->serv->on('Task',array($this,'onTask'));
        $this->serv->on('Finish',array($this,'onFinish'));
        $this->serv->start();
    }
    public function onStart($serv){
        echo "Start666\n";
    }
    public function onConnect($serv,$fd,$from_id){
        echo "Client {$fd} connect\n";
    }
    public function onClose($serv,$fd,$from_id){
        echo "Client {$fd} close connection\n";
    }

    public function onReceive(swoole_server $serv, $fd, $from_id, $data){
        echo "Get Message From Client {$fd}:{$data}\n";
        //我新加
        $data = [
            'task' => 'task_1',
            'params' => $data,
            'fd' => $fd
        ];
        $serv->task(json_encode($data));//通过serv的task方法,将这个数据传递过去;task只能传递字符串,所以需要将$data进行json化
    }

    public function onTask($serv, $task_id, $from_id, $data){
        echo "This Task {$task_id} from Worker {$from_id}\n";
        echo "Data: {$data}\n";
        //在这里会收到这个数据
        $data = json_decode($data, true);

        echo "Receive Task: {$task_id} from Worker {$from_id}\n";
        var_dump($data['params']);

        $serv->send($data['fd'], "爽哥在此");
        return "Finished";

    }
    public function onFinish($serv, $task_id, $data){
        echo "Task {$task_id} finish\n";
        echo "Result: {$data}\n";

    }
}
$server = new Server();