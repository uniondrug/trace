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
            $this->service = $service;
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
        // Swoole 环境下，通过Task的方式异步发送
        if (function_exists('app')) {
            $this->taskDispatcher->dispatch(TraceTask::class, $data);
        } else {
            call_user_func_array([$this, 'post'], [$data]);
        }
    }

    /**
     * 通过HTTP方式发送
     *
     * @param $data
     */
    public function post($data)
    {
        /**
         * @var \GuzzleHttp\Client $client
         */
        if ($this->di->has('tcpClient')) {
            $client = $this->di->getShared('tcpClient');
        } elseif ($this->di->has('httpClient')) {
            $client = $this->di->getShared('httpClient');
        } else {
            $client = new \GuzzleHttp\Client();
        }
        try {
            $options = [
                'json'     => $data,
                'timeout'  => $this->timeout,
                'no_trace' => true, // 关键，本投递不跟踪
            ];
            $client->post($this->service, $options);
        } catch (\Exception $e) {
            $this->di->getLogger('trace')->error(sprintf("[TraceClient] Send data to server failed: %s, data=%s", $e->getMessage(), json_encode($data)));
        }
    }
}
