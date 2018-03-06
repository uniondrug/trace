<?php
/**
 * 链路跟踪客户端TCP协议实现
 */

namespace Uniondrug\Trace;

/**
 * Class Client
 *
 * @package Uniondrug\Trace
 */
class Client
{
    /**
     *
     */
    const OK = '+';

    /**
     *
     */
    const ERROR = '-';

    /**
     *
     */
    const NL = "\r\n";

    /**
     * @var null|resource
     */
    private $handle = null;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var bool
     */
    private $silent_fail;

    /**
     * @var int
     */
    private $timeout = 30;

    /**
     * @var int
     */
    private $connect_timeout = 3;

    /**
     * Client constructor.
     *
     * @param bool $host
     * @param bool $port
     * @param bool $silent_fail
     * @param int  $timeout
     */
    public function __construct($host = false, $port = false, $silent_fail = false, $timeout = 60)
    {
        if ($host && $port) {
            $this->connect($host, $port, $silent_fail, $timeout);
        }
    }

    /**
     * Connect to server
     *
     * @param string $host
     * @param int    $port
     * @param bool   $silent_fail
     * @param int    $timeout
     */
    public function connect($host = '127.0.0.1', $port = 9530, $silent_fail = false, $timeout = 60)
    {
        $this->host = $host;
        $this->port = $port;
        $this->silent_fail = $silent_fail;
        $this->timeout = $timeout;

        if ($silent_fail) {
            $this->handle = @fsockopen($host, $port, $errno, $errstr, $this->connect_timeout);

            if (!$this->handle) {
                throw new \RuntimeException("Connection to server failed");
            }
        } else {
            $this->handle = fsockopen($host, $port, $errno, $errstr, $this->connect_timeout);
        }

        if (is_resource($this->handle)) {
            stream_set_timeout($this->handle, $this->timeout);
        }

        $greeting = $this->recv();
        if (!$greeting->success) {
            throw new \RuntimeException("Server not ready: " . $greeting->status);
        }
    }

    /**
     * Reconnect
     */
    public function reconnect()
    {
        $this->__destruct();
        $this->connect($this->host, $this->port, $this->silent_fail);
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Read response from server
     *
     * @param bool $multiLine
     *
     * @return \stdClass
     */
    public function recv($multiLine = false)
    {
        if (!is_resource($this->handle)) {
            throw new \RuntimeException('Connection not ready');
        }

        $response = new \stdClass();
        $char = fgetc($this->handle);
        $status = fgets($this->handle, 4096);

        if ($char == self::OK) {
            $response->success = true;
            $response->status = trim($status);
        } else if ($char == self::ERROR) {
            $response->success = false;
            $response->status = trim($status);
            return $response;
        } else {
            $response->success = false;
            $response->status = "Invalid response: " . $char . $status;
            return $response;
        }

        if ($multiLine) {
            $response->data = [];
            while ($data = fgets($this->handle, 4096)) {
                $data = trim($data);
                if ($data == '.') {
                    break;
                }
                $response->data[] = $data;
            }
        }

        return $response;
    }

    /**
     * Send command to register server
     *
     * @param $data
     *
     * @return $this
     */
    public function send($data)
    {
        if (!is_resource($this->handle)) {
            throw new \RuntimeException("Connection not ready");
        }

        if (!$this->noop()) {
            $this->reconnect();
        }

        try {
            $res = @fwrite($this->handle, $data . static::NL);
            if ($res === false) {
                throw new \RuntimeException("Write to server failed");
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException("Send data failed: " . $e->getMessage());
        }

        return $this;
    }

    public function noop()
    {
        try {
            @fwrite($this->handle, 'noop' . static::NL);
            $res = $this->recv();

            return $res->success;
        } catch (\Throwable $e) {
            echo $e->getMessage();

            return false;
        }
    }
}