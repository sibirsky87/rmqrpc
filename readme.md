Example:

require_once __DIR__ . '/vendor/autoload.php';

use Sibirsky87\Rmqrpc\AbstractRpcModel;

class RpcServer extends AbstractRpcModel
{

    protected $queue = 'rpc_queue';

    public function getResult($message)
    {
        return  " response to " . print_r($message, TRUE);
    }

}

$config = [
    'host'     => "127.0.0.1",
    'port'     => 5672,
    'user'     => 'guest',
    'password' => 'guest',
    'target'   => 1
];
$server  = new RpcServer($config);
$server->process();

