<?php
/**
 * 注册中心客户端
 */

namespace Uniondrug\Trace;

use Uniondrug\Framework\Injectable;

class TraceClient extends Injectable
{
    /**
     * @var \Uniondrug\Trace\Client
     */
    protected static $tcpClient = null;

    /**
     * @var \GuzzleHttp\Client
     */
    protected static $httpClient = null;

    /**
     * @var string
     */
    protected $service = null;

    /**
     * @var string
     */
    protected $method = null;

    /**
     * @var int
     */
    protected $timeout = 30;

    /**
     * @var string
     */
    protected $host = null;

    /**
     * @var int
     */
    protected $port = null;

    /**
     * TraceClient constructor.
     */
    public function __construct()
    {
        $this->configuration();
    }

    /**
     * @param array $data
     */
    public function send($data = [])
    {
        if ($this->method) {
            call_user_func_array([$this, $this->method], [$data]);
        } else {
            throw new \RuntimeException('No valid method found');
        }
    }

    public function sendHttp($data)
    {
        if (static::$httpClient == null) {
            static::$httpClient = new \GuzzleHttp\Client();
        }
        try {
            $options = [
                'json'    => $data,
                'timeout' => $this->timeout,
            ];
            static::$httpClient->post($this->service, $options);
        } catch (\Exception $e) {
            $this->di->getLogger('trace')->error(sprintf("[TraceClient] Send data to server failed: %s, data=%s", $e->getMessage(), json_encode($data)));
        }
    }

    public function sendTcp($data)
    {
        if (static::$tcpClient == null) {
            static::$tcpClient = new Client($this->host, $this->port, true, $this->timeout);
        }
        try {
            static::$tcpClient->send(json_encode($data))->recv();
        } catch (\Exception $e) {
            $this->di->getLogger('trace')->error(sprintf("[TraceClient] Send data to server failed: %s, data=%s", $e->getMessage(), json_encode($data)));
        }
    }

    protected function configuration()
    {
        if ($service = $this->config->path('trace.service')) {
            $this->service = $service;
            $this->method = 'sendHttp';
        }
        if (($host = $this->config->path('trace.host')) && ($port = $this->config->path('trace.port'))) {
            $this->host = $host;
            $this->port = $port;
            $this->method = 'sendTcp';
        }
        if ($timeout = $this->config->path('trace.timeout', 30)) {
            $this->timeout = $timeout;
        }
    }
}
