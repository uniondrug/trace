<?php
/**
 * TraceTask.php
 *
 */

namespace Uniondrug\Trace;

use Uniondrug\Server\Task;

class TraceTask extends Task\TaskHandler
{
    public function handle($data = [])
    {
        $traceClient = $this->traceClient;
        call_user_func_array([$traceClient, $traceClient->getMethod()], [$data]);
    }
}
