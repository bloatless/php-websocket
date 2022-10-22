<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

class PushClient
{
    /**
     * Path to unix domain socket.
     *
     * @var string $ipcSocketPath
     */
    private string $ipcSocketPath;

    private const MAX_PAYLOAD_LENGTH = 65536;

    public function __construct(string $ipcSocketPath = '/tmp/phpwss.sock')
    {
        $this->ipcSocketPath = $ipcSocketPath;
    }

    /**
     * Push server control command.
     *
     * @param string $command
     * @param array $data
     * @return bool
     */
    public function sendServerCommand(string $command, array $data): bool
    {
        $payload = IPCPayloadFactory::makeServerPayload($command, $data);

        return $this->sendPayloadToServer($payload);
    }

    /**
     * Pushes data into an application running within the websocket server.
     *
     * @param string $applicationName
     * @param array $data
     * @return bool
     */
    public function sendToApplication(string $applicationName, array $data): bool
    {
        $payload = IPCPayloadFactory::makeApplicationPayload($applicationName, $data);

        return $this->sendPayloadToServer($payload);
    }

    /**
     * Pushes payload into the websocket server using a unix domain socket.
     *
     * @param IPCPayload $payload
     * @return bool
     */
    private function sendPayloadToServer(IPCPayload $payload): bool
    {
        $dataToSend = $payload->asJson();
        $dataLength = strlen($dataToSend);
        if ($dataLength > self::MAX_PAYLOAD_LENGTH) {
            throw new \RuntimeException(
                sprintf(
                    'IPC payload exceeds max length of %d bytes. (%d bytes given.)',
                    self::MAX_PAYLOAD_LENGTH,
                    $dataLength
                )
            );
        }
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new \RuntimeException('Could not open ipc socket.');
        }
        $bytesSend = socket_sendto($socket, $dataToSend, $dataLength, MSG_EOF, $this->ipcSocketPath, 0);
        if ($bytesSend <= 0) {
            throw new \RuntimeException('Could not sent data to IPC socket.');
        }
        socket_close($socket);

        return true;
    }
}
