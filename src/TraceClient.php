<?php
/**
 * 链路跟踪客户端
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
     * 采集服务
     *
     * @var string
     */
    protected $service = null;

    /**
     * API path
     *
     * @var string
     */
    protected $path = '/collector';

    /**
     * 投递超时
     *
     * @var int
     */
    protected $timeout = 1;

    /**
     * TraceClient constructor.
     */
    public function __construct()
    {
        if ($service = $this->config->path('trace.service')) {
            $this->service = rtrim($service, '/');
        }
        if ($timeout = $this->config->path('trace.timeout', 30)) {
            $this->timeout = $timeout;
        }
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
        // Swoole 环境下: 普通的Worker进程才做异步发送，否则，同步发送
        if (function_exists('app') && isset(swoole()->worker_pid) && !swoole()->taskworker) {
            $this->taskDispatcher->dispatchByProcess(TraceTask::class, $data);
        } else {
            call_user_func_array([$this, 'post'], [$data]);
        }
    }

    /**
     * 通过HTTP方式发送
     *
     * @param $data
     *
     * @return bool
     */
    public function post($data)
    {
        /**
         * @var \GuzzleHttp\Client $client
         */
        if ('tcp' === strtolower(substr($this->service, 0, 3))) {
            if ($this->di->has('tcpClient')) {
                $client = $this->di->getShared('tcpClient');
            } else {
                $this->di->getLogger('trace')->error(sprintf("[TraceClient] TcpClient not installed."));
                return false;
            }
        } else {
            if ($this->di->has('httpClient')) {
                $client = $this->di->getShared('httpClient');
            } else {
                $client = new \GuzzleHttp\Client();
            }
        }
        try {
            $options = [
                'json'     => $data,
                'timeout'  => $this->timeout,
                'no_trace' => true, // 关键，本投递不跟踪
            ];
            $client->post($this->service . $this->path, $options);
        } catch (\Exception $e) {
            $this->di->getLogger('trace')->error(sprintf("[TraceClient] Send data to server failed: %s, data=%s", $e->getMessage(), json_encode($data)));
        }
    }
}
