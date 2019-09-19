<?php

if (!isset($argv[1]) || !filter_var($argv[1], FILTER_VALIDATE_IP)) {
    echo 'usage: client.php ip-address' . PHP_EOL;
    exit(1);
}

$ip           = $argv[1];
$clientSocket = stream_socket_client("tcp://{$ip}:9999", $errorCode, $errorMessage, 3600);

if (!$clientSocket) {
    echo $errorMessage . PHP_EOL;
    exit(1);
}

while (true) {
    $read[] = $clientSocket;
    $read[] = STDIN;

    if (false === ($changedStreamsQuantity = stream_select($read, $write, $except, 0))) {
        echo 'Error!' . PHP_EOL;
        continue;
    }

    if ($changedStreamsQuantity > 0) {
        foreach ($read as $sock) {
            if ($sock == STDIN) {
                $message = readLineFromConsole(STDIN);
                stream_socket_sendto($clientSocket, $message);
            }
            else {
                $response = stream_socket_recvfrom($sock, 1024);

                if (strlen($response) !== 0) {
                    $responseMode = substr($response, 0, 1);

                    $textLine = '';

                    switch ($responseMode) {
                        case 'u' :
                            $response = substr($response, 1);
                            $name     = substr($response, 0, 20);
                            $message  = substr($response, 20);
                            $textLine = $name . ': ' . $message;
                            break;
                        case 's' :
                            $textLine = substr($response, 1);
                            break;
                    }

                    echo '[' . date('H:i:s') . '] ' . $textLine;
                }
            }
        }
    }
}

function readLineFromConsole($inputStream)
{
    $message = '';

    while (false !== ($char = fgetc($inputStream))) {
        $message .= $char;

        // Backspace - remove the previous letter from console
        if (ord($char) == 127) {
            $message = mb_substr($message, 0, -2);
            echo "\033[1D";
        }

        // Enter - send message
        if (ord($char) == 10) {
            echo "\033[1A";
            return $message;
        }
    }
}