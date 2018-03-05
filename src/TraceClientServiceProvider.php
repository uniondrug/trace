<?php

namespace Uniondrug\Trace;

use Phalcon\Di\ServiceProviderInterface;

class TraceClientServiceProvider implements ServiceProviderInterface
{
    public function register(\Phalcon\DiInterface $di)
    {
        $di->set(
            'traceClient',
            function () {
                $client = new TraceClient(
                    $this->getConfig()->path('trace.host', '127.0.0.1'),
                    $this->getConfig()->path('trace.port', 9530),
                    true,
                    (int) $this->getConfig()->path('trace.timeout', 30)
                );

                return $client;
            }
        );
    }
}
