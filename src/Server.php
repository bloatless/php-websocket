<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

use Bloatless\WebSocket\Application\ApplicationInterface;

/**
 * Simple WebSocket server implementation in PHP.
 *
 * @author Simon Samtleben <foo@bloatless.org>
 * @author Nico Kaiser <nico@kaiser.me>
 * @version 2.0
 */
class Server
{
    /**
     * @var resource $master Holds the master socket
     */
    protected $master;

    /**
     * @var resource $icpSocket
     */
    private $icpSocket;

    /**
     * @var string $ipcSocketPath
     */
    private string $ipcSocketPath;

    /**
     * @var array Holds all connected sockets
     */
    protected array $allsockets = [];

    /**
     * @var resource $context
     */
    protected $context = null;

    /**
     * @var array $clients
     */
    protected array $clients = [];

    /**
     * @var array $applications
     */
    protected array $applications = [];

    /**
     * @var array $ipStorage
     */
    private array $ipStorage = [];

    /**
     * @var bool $checkOrigin
     */
    private bool $checkOrigin = true;

    /**
     * @var array $allowedOrigins
     */
    private array $allowedOrigins = [];

    /**
     * @var int $maxClients
     */
    private int $maxClients = 30;

    /**
     * @var int $maxConnectionsPerIp
     */
    private int $maxConnectionsPerIp = 5;

    /**
     * @var TimerCollection $timers
     */
    private TimerCollection $timers;

    /**
     * @param string $host
     * @param int $port
     * @param string $ipcSocketPath
     */
    public function __construct(
        string $host = 'localhost',
        int $port = 8000,
        string $ipcSocketPath = '/tmp/phpwss.sock'
    ) {
        ob_implicit_flush(); // php7.x -> int, php8.x => bool, default 1 or true
        $this->createSocket($host, $port);
        $this->openIPCSocket($ipcSocketPath);
        $this->timers = new TimerCollection();
        $this->log('Server created');
    }

    /**
     * Main server loop.
     * Listens for connections, handles connectes/disconnectes, e.g.
     *
     * @return void
     */
    public function run(): void
    {
        while (true) {
            $this->timers->runAll();
          
            $changed_sockets = $this->allsockets;
            @stream_select($changed_sockets, $write, $except, 0, 5000);
            foreach ($changed_sockets as $socket) {
                if ($socket == $this->master) {
                    if (($ressource = stream_socket_accept($this->master)) === false) {
                        $this->log('Socket error: ' . socket_strerror(socket_last_error($ressource)));
                        continue;
                    } else {
                        $client = $this->createConnection($ressource);
                        $this->clients[(int)$ressource] = $client;
                        $this->allsockets[] = $ressource;

                        if (count($this->clients) > $this->maxClients) {
                            $client->onDisconnect();
                            if ($this->hasApplication('status')) {
                                $this->getApplication('status')->statusMsg(
                                    'Attention: Client Limit Reached!',
                                    'warning'
                                );
                            }
                            continue;
                        }

                        $this->addIpToStorage($client->getClientIp());
                        if ($this->checkMaxConnectionsPerIp($client->getClientIp()) === false) {
                            $client->onDisconnect();
                            if ($this->hasApplication('status')) {
                                $this->getApplication('status')->statusMsg(
                                    'Connection/Ip limit for ip ' . $client->getClientIp() . ' was reached!',
                                    'warning'
                                );
                            }
                            continue;
                        }
                    }
                } else {
                    /** @var Connection $client */
                    $client = $this->clients[(int)$socket];
                    if (!is_object($client)) {
                        unset($this->clients[(int)$socket]);
                        continue;
                    }

                    try {
                        $data = $this->readBuffer($socket);
                    } catch (\RuntimeException $e) {
                        $this->removeClientOnError($client);
                        continue;
                    }
                    $bytes = strlen($data);
                    if ($bytes === 0) {
                        $client->onDisconnect();
                        continue;
                    }

                    $client->onData($data);
                }
            }

            $this->handleIPC();
        }
    }

    /**
     * Checks if an application is registred.
     *
     * @param string $key
     * @return bool
     */
    public function hasApplication(string $key): bool
    {
        if (empty($key)) {
            return false;
        }

        return array_key_exists($key, $this->applications);
    }

    /**
     * Returns a server application.
     *
     * @param string $key Name of application.
     * @return ApplicationInterface The application object.
     */
    public function getApplication(string $key): ApplicationInterface
    {
        if ($this->hasApplication($key) === false) {
            throw new \RuntimeException('Unknown application requested.');
        }
        return $this->applications[$key];
    }


    /**
     * Adds a new application object to the application storage.
     *
     * @param string $key Name of application.
     * @param ApplicationInterface $application The application object.
     * @return void
     */
    public function registerApplication(string $key, ApplicationInterface $application): void
    {
        $this->applications[$key] = $application;

        // status is kind of a system-app, needs some special cases:
        if ($key === 'status') {
            $serverInfo = array(
                'maxClients' => $this->maxClients,
                'maxConnectionsPerIp' => $this->maxConnectionsPerIp,
            );
            $this->applications[$key]->setServerInfo($serverInfo);
        }
    }

    /**
     * Echos a message to standard output.
     *
     * @param string $message Message to display.
     * @param string $type Type of message.
     * @return void
     */
    public function log(string $message, string $type = 'info'): void
    {
        echo date('Y-m-d H:i:s') . ' [' . ($type ? $type : 'error') . '] ' . $message . PHP_EOL;
    }

    /**
     * Removes a client from client storage.
     *
     * @param Connection $client
     * @return void
     */
    public function removeClientOnClose(Connection $client): void
    {
        $clientId = $client->getClientId();
        $clientIp = $client->getClientIp();
        $clientPort = $client->getClientPort();
        $resource = $client->getClientSocket();

        $this->removeIpFromStorage($client->getClientIp());
        unset($this->clients[(int)$resource]);
        $index = array_search($resource, $this->allsockets);
        unset($this->allsockets[$index], $client);

        // trigger status application:
        if ($this->hasApplication('status')) {
            $this->getApplication('status')->clientDisconnected($clientIp, $clientPort);
        }
        unset($clientId, $clientIp, $clientPort, $resource);
    }

    /**
     * Removes a client and all references in case of timeout/error.
     *
     * @param Connection $client The client object to remove.
     * @return void
     */
    public function removeClientOnError(Connection $client): void
    {
        // remove reference in clients app:
        if ($client->getClientApplication() !== null) {
            $client->getClientApplication()->onDisconnect($client);
        }

        $resource = $client->getClientSocket();
        $clientId = $client->getClientId();
        $clientIp = $client->getClientIp();
        $clientPort = $client->getClientPort();
        $this->removeIpFromStorage($client->getClientIp());
        unset($this->clients[(int)$resource]);
        $index = array_search($resource, $this->allsockets);
        unset($this->allsockets[$index], $client);

        // trigger status application:
        if ($this->hasApplication('status')) {
            $this->getApplication('status')->clientDisconnected($clientIp, $clientPort);
        }
        unset($resource, $clientId, $clientIp, $clientPort);
    }

    /**
     * Checks if the submitted origin (part of websocket handshake) is allowed
     * to connect. Allowed origins can be set at server startup.
     *
     * @param string $domain The origin-domain from websocket handshake.
     * @return bool If domain is allowed to connect method returns true.
     */
    public function checkOrigin(string $domain): bool
    {
        $domain = str_replace('http://', '', $domain);
        $domain = str_replace('https://', '', $domain);
        $domain = str_replace('www.', '', $domain);
        $domain = str_replace('/', '', $domain);

        return isset($this->allowedOrigins[$domain]);
    }

    /**
     * Creates a connection from a socket resource
     *
     * @param resource $resource A socket resource
     * @return Connection
     */
    protected function createConnection($resource): Connection
    {
        return new Connection($this, $resource);
    }

    /**
     * Adds a new ip to ip storage.
     *
     * @param string $ip An ip address.
     * @return void
     */
    private function addIpToStorage(string $ip): void
    {
        if (isset($this->ipStorage[$ip])) {
            $this->ipStorage[$ip]++;
        } else {
            $this->ipStorage[$ip] = 1;
        }
    }

    /**
     * Removes an ip from ip storage.
     *
     * @param string $ip An ip address.
     * @return bool True if ip could be removed.
     */
    private function removeIpFromStorage(string $ip): bool
    {
        if (!isset($this->ipStorage[$ip])) {
            return false;
        }
        if ($this->ipStorage[$ip] === 1) {
            unset($this->ipStorage[$ip]);
            return true;
        }
        $this->ipStorage[$ip]--;

        return true;
    }

    /**
     * Checks if an ip has reached the maximum connection limit.
     *
     * @param string $ip An ip address.
     * @return bool False if ip has reached max. connection limit. True if connection is allowed.
     */
    private function checkMaxConnectionsPerIp(string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }
        if (!isset($this->ipStorage[$ip])) {
            return true;
        }
        return ($this->ipStorage[$ip] > $this->maxConnectionsPerIp) ? false : true;
    }

    /**
     * Set whether the client origin should be checked on new connections.
     *
     * @param bool $doOriginCheck
     * @return bool True if value could validated and set successfully.
     */
    public function setCheckOrigin(bool $doOriginCheck): bool
    {
        if (is_bool($doOriginCheck) === false) {
            return false;
        }
        $this->checkOrigin = $doOriginCheck;
        return true;
    }

    /**
     * Return value indicating if client origins are checked.
     * @return bool True if origins are checked.
     */
    public function getCheckOrigin(): bool
    {
        return $this->checkOrigin;
    }

    /**
     * Adds a domain to the allowed origin storage.
     *
     * @param string $domain A domain name from which connections to server are allowed.
     * @return bool True if domain was added to storage.
     */
    public function setAllowedOrigin(string $domain): bool
    {
        $domain = str_replace('http://', '', $domain);
        $domain = str_replace('www.', '', $domain);
        $domain = (strpos($domain, '/') !== false) ? substr($domain, 0, strpos($domain, '/')) : $domain;
        if (empty($domain)) {
            return false;
        }
        $this->allowedOrigins[$domain] = true;
        return true;
    }

    /**
     * Sets value for the max. connection per ip to this server.
     *
     * @param int $limit Connection limit for an ip.
     * @return bool True if value could be set.
     */
    public function setMaxConnectionsPerIp(int $limit): bool
    {
        if (!is_int($limit)) {
            return false;
        }
        $this->maxConnectionsPerIp = $limit;
        return true;
    }

    /**
     * Returns the max. connections per ip value.
     *
     * @return int Max. simoultanous  allowed connections for an ip to this server.
     */
    public function getMaxConnectionsPerIp(): int
    {
        return $this->maxConnectionsPerIp;
    }

    /**
     * Sets how many clients are allowed to connect to server until no more
     * connections are accepted.
     *
     * @param int $max Max. total connections to server.
     * @return bool True if value could be set.
     */
    public function setMaxClients(int $max): bool
    {
        if ((int)$max === 0) {
            return false;
        }
        $this->maxClients = (int)$max;
        return true;
    }

    /**
     * Returns total max. connection limit of server.
     *
     * @return int Max. connections to this server.
     */
    public function getMaxClients(): int
    {
        return $this->maxClients;
    }

    /**
     * Adds a periodic timer.
     *
     * @param int $interval Interval in microseconds.
     * @param callable $task
     */
    public function addTimer(int $interval, callable $task): void
    {
        $this->timers->addTimer(new Timer($interval, $task));
    }

    /**
     * Create a socket on given host/port
     *
     * @param string $host The host/bind address to use
     * @param int $port The actual port to bind on
     * @throws \RuntimeException
     * @return void
     */
    private function createSocket(string $host, int $port): void
    {
        $protocol = 'tcp://';
        $url = $protocol . $host . ':' . $port;
        $this->context = stream_context_create();
        $this->master = stream_socket_server(
            $url,
            $errno,
            $err,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $this->context
        );
        if ($this->master === false) {
            throw new \RuntimeException('Error creating socket: ' . $err);
        }

        $this->allsockets[] = $this->master;
    }

    /**
     * Reads from stream.
     *
     * @param $resource
     * @throws \RuntimeException
     * @return string
     */
    protected function readBuffer($resource): string
    {
        $buffer = '';
        $buffsize = 8192;
        $metadata['unread_bytes'] = 0;
        do {
            if (feof($resource)) {
                throw new \RuntimeException('Could not read from stream.');
            }
            $result = fread($resource, $buffsize);
            if ($result === false || feof($resource)) {
                throw new \RuntimeException('Could not read from stream.');
            }
            $buffer .= $result;
            $metadata = stream_get_meta_data($resource);
            $buffsize = ($metadata['unread_bytes'] > $buffsize) ? $buffsize : $metadata['unread_bytes'];
        } while ($metadata['unread_bytes'] > 0);

        return $buffer;
    }

    /**
     * Write to stream.
     *
     * @param $resource
     * @param string $string
     * @return int
     */
    public function writeBuffer($resource, string $string): int
    {
        $stringLength = strlen($string);
        if ($stringLength === 0) {
            return 0;
        }

        for ($written = 0; $written < $stringLength; $written += $fwrite) {
            $fwrite = @fwrite($resource, substr($string, $written));
            if ($fwrite === false) {
                throw new \RuntimeException('Could not write to stream.');
            }
            if ($fwrite === 0) {
                throw new \RuntimeException('Could not write to stream.');
            }
        }

        return $written;
    }

    /**
     * Opens a Unix-Domain-Socket to listen for inputs from other applications.
     *
     * @param string $ipcSocketPath
     * @throws \RuntimeException
     * @return void
     */
    private function openIPCSocket(string $ipcSocketPath): void
    {
        if (file_exists($ipcSocketPath)) {
            unlink($ipcSocketPath);
        }
        $this->icpSocket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($this->icpSocket === false) {
            throw new \RuntimeException('Could not open ipc socket.');
        }
        if (socket_set_nonblock($this->icpSocket) === false) {
            throw new \RuntimeException('Could not set nonblock mode for ipc socket.');
        }
        if (socket_bind($this->icpSocket, $ipcSocketPath) === false) {
            throw new \RuntimeException('Could not bind to ipc socket.');
        }
        $this->ipcSocketPath = $ipcSocketPath;
    }

    /**
     * Checks IPC socket for input and processes data.
     *
     * @return void
     */
    private function handleIPC(): void
    {
        $buffer = '';
        $bytesReceived = socket_recvfrom($this->icpSocket, $buffer, 65536, 0, $this->ipcSocketPath);
        if ($bytesReceived === false) {
            return;
        }
        if ($bytesReceived <= 0) {
            return;
        }

        $payload = IPCPayloadFactory::fromJson($buffer);
        switch ($payload->type) {
            case IPCPayload::TYPE_SERVER:
                // @todo handle server command
                break;
            case IPCPayload::TYPE_APPLICATION:
                $app = $this->getApplication($payload->action);
                $app->onIPCData($payload->data);
                break;
            default:
                throw new \RuntimeException('Invalid IPC message received.');
        }
    }
}
