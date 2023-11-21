<?php

namespace FlarumS\Server;

use FlarumS\Console\Color;

/**
 * Class Httpd
 * @package FlarumS\Server
 * @author <trint.dev@gmail.com>
 */
class Httpd
{

    /**
     * @var string
     */
    public $host;
    /**
     * @var int
     */
    public $port;
    /**
     * @var array
     */
    public $setting = [];
    /**
     * @var array
     */
    public $path = [];

    /**
     * @var Psr\Http\Server\RequestHandlerInterface
     */
    private $_request_handle;

    /**
     * @var string
     */
    const SERVER_NAME = 'flarum-httpd';

    /**
     * @var \Swoole\Http\Server
     */
    protected $_server;

    /**
     * @param array $path
     * @param array $setting
     */
    public function __construct(array $path, array $setting)
    {
        $this->path    = $path;
        $this->setting = $setting;
        $this->host    = $this->setting['server']['host'];
        $this->port    = $this->setting['server']['port'];
    }

    /**
     * @return bool
     */
    public function start()
    {
        $this->_server = new \Swoole\Http\Server($this->host, $this->port);
        $setting = $this->setting;
        unset($setting['server']);
        $this->_server->set($setting);

        $this->_server->on(SwooleEvent::START, [$this, 'onStart']);
        $this->_server->on(SwooleEvent::MANAGER_START, [$this, 'onManagerStart']);
        $this->_server->on(SwooleEvent::WORKER_START, [$this, 'onWorkerStart']);
        $this->_server->on(SwooleEvent::REQUEST, [$this, 'onRequest']);

        $this->welcome();
        return $this->_server->start();
    }

    public function onStart(\Swoole\Http\Server $server)
    {
        $this->setProcessTitle(static::SERVER_NAME . ": master {$this->host}:{$this->port}");
    }

    public function onManagerStart($server)
    {
        $this->setProcessTitle(static::SERVER_NAME . ": manager");
    }

    public function onWorkerStart(\Swoole\Http\Server $server, int $workerId)
    {
        if ($workerId < $server->setting['worker_num']) {
            $this->setProcessTitle(static::SERVER_NAME . ": worker #{$workerId}");
        } else {
            $this->setProcessTitle(static::SERVER_NAME . ": task #{$workerId}");
        }

        $this->_request_handle = \Flarum\Foundation\Site::fromPaths($this->path)->bootApp()->getRequestHandler();
    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        try {
            if ($request->server['path_info'] != '/'
                && file_exists($this->path['public'] . '/' . $request->server['path_info'])
            ) {
                $response->sendfile($this->path['public'] . '/' . $request->server['path_info']);
            } else {
                $psrResponse = $this->_request_handle->handle((new Psr7RequestBuilder)->build($request));
                (new SwooleResponseEmitter)->toSwoole($psrResponse, $response);
            }
        } catch (\Throwable $e) {
            $errors = [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'type'    => get_class($e),
                'trace'   => $e->getTraceAsString(),
            ];
            print_r($errors);
        }
    }

    protected function welcome()
    {
        $swooleVersion = swoole_version();
        $phpVersion    = PHP_VERSION;
        echo <<<EOL
        ___________.__
        \_   _____/|  | _____ _______ __ __  _____
         |    __)  |  | \__  \\_  __ \  |  \/     \
         |     \   |  |__/ __ \|  | \/  |  /  Y Y  \
         \___  /   |____(____  /__|  |____/|__|_|  /
             \/              \/                  \/


EOL;
        (new Color)->println('Server         Name:      ' . static::SERVER_NAME);
        (new Color)->println('System         Name:      ' . strtolower(PHP_OS));
        (new Color)->println("PHP            Version:   {$phpVersion}");
        (new Color)->println("Swoole         Version:   {$swooleVersion}");
        $this->setting['max_request'] == 1 && (new Color)->println('Hot            Update:    enabled');
        $this->setting['enable_coroutine'] && (new Color)->println('Coroutine      Mode:      enabled');
        (new Color)->println("Listen         Addr:      {$this->host}");
        (new Color)->println("Listen         Port:      {$this->port}");
        (new Color)->println('Reactor        Num:       ' . $this->setting['reactor_num']);
        (new Color)->println('Worker         Num:       ' . $this->setting['worker_num']);
    }

    private function setProcessTitle($title)
    {
        if (!function_exists('cli_set_process_title') ||
            (stripos(PHP_OS, 'WIN') !== false) ||
            (stripos(PHP_OS, 'Darwin') !== false)
        ) {
            return false;
        }
        return @cli_set_process_title($title);
    }

}
