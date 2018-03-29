<?php
/**
 * 注册中心客户端
 */

namespace Uniondrug\Trace;

use Uniondrug\Framework\Injectable;

/**
 * Class TraceClient
 *
 * @package Uniondrug\Trace
 */
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
     * 发送方式
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 发送入口
     *
     * @param array $data
     *
     * @throws \Uniondrug\Packet\Exceptions\PacketException
     */
    public function send($data = [])
    {
        if ($this->method) {
            // Swoole 环境下，通过Task的方式异步发送
            if ($this->di->has('taskDispatcher')) {
                $this->taskDispatcher->dispatch(TraceTask::class, $data);
            } else {
                call_user_func_array([$this, $this->method], [$data]);
            }
        } else {
            throw new \RuntimeException('No valid method found');
        }
    }

    /**
     * 通过HTTP方式发送
     *
     * @param $data
     */
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

    /**
     * 通过TCP的方式发送，TCP方式可以保持连接
     *
     * @param $data
     */
    public function sendTcp($data)
    {
        if (static::$tcpClient == null) {
            static::$tcpClient = new Client($this->host, $this->port, true, $this->timeout);
        }
        try {
            $noop = static::$tcpClient->send('noop')->recv();
            if (!$noop->success) {
                static::$tcpClient->reconnect();
            }

            static::$tcpClient->send(json_encode($data))->recv();
        } catch (\Exception $e) {
            $this->di->getLogger('trace')->error(sprintf("[TraceClient] Send data to server failed: %s, data=%s", $e->getMessage(), json_encode($data)));
        }
    }

    /**
     * 初始化配置
     */
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
