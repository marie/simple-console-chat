<?php

$serverSocket = stream_socket_server("tcp://0.0.0.0:9999", $errorCode, $errorMessage);

if (!$serverSocket) {
    echo $errorMessage . PHP_EOL;
    exit(1);
}

$listenedSockets[] = $serverSocket;

while (true) {
    $read = $listenedSockets;
    if (false === ($changedStreamsQuantity = stream_select($read, $write, $except, 0))) {
        echo 'Error!' . PHP_EOL;
        continue;
    }

    if ($changedStreamsQuantity > 0) {
        foreach ($read as $readSocketName => $readSocket) {
            if ($readSocket === $serverSocket) {
                $connectedSocket                       = stream_socket_accept($readSocket, 1);
                $connectedSocketName                   = stream_socket_get_name($connectedSocket, true);
                $listenedSockets[$connectedSocketName] = $connectedSocket;
                foreach ($listenedSockets as $listenedSocket) {
                    if ($listenedSocket !== $serverSocket) {
                        stream_socket_sendto($listenedSocket, "sUser [{$connectedSocketName}] connected" . PHP_EOL);
                        echo "User [{$connectedSocketName}] connected" . PHP_EOL;
                    }
                }
            }
            else {
                if (feof($readSocket)) {
                    echo "Connection closed" . PHP_EOL;

                    fclose($readSocket);
                    $keyToDel = array_search($readSocket, $listenedSockets, true);
                    unset($listenedSockets[$keyToDel]);
                    continue;
                }

                $message = stream_socket_recvfrom($readSocket, 1024);

                if (strlen($message) !== 0) {
                    $readSocketName = str_pad($readSocketName, 20);

                    foreach ($listenedSockets as $listenedSocket) {
                        if ($listenedSocket !== $serverSocket) {
                            stream_socket_sendto($listenedSocket, 'u' . $readSocketName . $message);
                        }
                    }
                }
            }
        }
    }
}
