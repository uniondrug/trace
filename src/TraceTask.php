<?php
/**
 * 异步投递任务处理器
 */

namespace Uniondrug\Trace;

use Uniondrug\Server\Task;

/**
 * Class TraceTask
 *
 * @package Uniondrug\Trace
 * @property \Uniondrug\Trace\TraceClient $traceClient
 */
class TraceTask extends Task\TaskHandler
{
    public function handle($data = [])
    {
        $traceClient = $this->traceClient;
        call_user_func_array([$traceClient, 'post'], [$data]);
    }
}
