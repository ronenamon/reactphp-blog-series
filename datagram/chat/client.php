<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class UdpChatClient
{
    /** @var  React\EventLoop\LoopInterface; */
    protected $loop;

    /** @var React\Stream\ReadableStreamInterface;  */
    protected $stdin;

    /** @var  React\Datagram\Socket  */
    protected $socket;

    protected $name = '';

    public function run()
    {
        $this->loop = React\EventLoop\Factory::create();
        $factory = new React\Datagram\Factory($this->loop);

        $this->stdin = new React\Stream\ReadableResourceStream(STDIN, $this->loop);
        $this->stdin->on('data', [$this, 'processInput']);

        $factory->createClient('localhost:1234')
            ->then(
                [$this, 'initClient'],
                function (Exception $error) {
                    echo "ERROR: {$error->getMessage()}\n";
                });

        $this->loop->run();
    }

    public function initClient(React\Datagram\Socket $client)
    {
        $this->socket = $client;

        $this->socket->on('message', function ($message) {
            echo $message . "\n";
        });

        $this->socket->on('close', function () {
            $this->loop->stop();
        });

        echo "Enter your name: ";
    }

    public function processInput($data)
    {
        $data = trim($data);

        if (empty($this->name)) {
            $this->name = $data;
            $this->sendData('', 'enter');
            return;
        }

        if ($data == ':exit') {
            $this->sendData('', 'leave');
            $this->socket->end();
            return;
        }

        $this->sendData($data);
    }

    protected function sendData($message, $type = 'message')
    {
        $data = [
            'type'    => $type,
            'name'    => $this->name,
            'message' => $message,
        ];

        $this->socket->send(json_encode($data));
    }
}

(new UdpChatClient())->run();
