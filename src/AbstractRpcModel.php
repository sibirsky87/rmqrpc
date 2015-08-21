<?php

namespace Sibirsky87\Rmqrpc;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//use Amp;

abstract class AbstractRpcModel
{

    public $consumed        = 0;
    public $target          = 0;
    protected $connection;
    protected $channel;
    protected $queue;
    public static $callback = "callback";

    public function __construct($config = [
        'host'     => "127.0.0.1",
        'port'     => 5672,
        'user'     => 'guest',
        'password' => 'guest',
        'target'   => NULL
    ])
    {
        $this->connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['password']);
        $this->channel    = $this->connection->channel();
        $this->target     = isset($config['target']) ? $config['target'] : NULL;
        static::$callback = $this;
    }

    public function process()
    {
        $this->channel->queue_declare($this->queue, false, false, false, false);

        echo " [x] Awaiting RPC requests\n";
        $amqCallback = static::getCallback();
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($this->queue, '', false, false, false, false, $amqCallback);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->connection->close();
    }

    public function getCallback()
    {
        return function($req) {
            $response = static::$callback->getResult($req->body);

            $msg = new AMQPMessage(
                    (string) $response, array('correlation_id' => $req->get('correlation_id'))
            );

            $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));
            $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);

            $this->consumed++;
            if ($this->consumed == $this->target) {
                $req->delivery_info['channel']->basic_cancel($req->delivery_info['consumer_tag']);
            }
        };
    }

    abstract public function getResult($message);
}
