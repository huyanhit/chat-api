<?php

namespace App\Services;

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class SocketService extends Service {
    public function emit($data){
        $client = new Client(new Version2X('127.0.0.1:6969', [
            'headers' => [
                'X-My-Header: websocket rocks',
                'Authorization: Bearer ',
            ],
            'context' => [
                'ssl' => ['verify_peer_name' => false, 'verify_peer' => false]
            ]
        ]));

        $client->initialize();
        $client->emit('push-socket', $data);
        $client->close();
    }
}
