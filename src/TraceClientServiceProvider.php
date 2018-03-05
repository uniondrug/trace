<?php

namespace Uniondrug\Trace;

use Phalcon\Di\ServiceProviderInterface;

class TraceClientServiceProvider implements ServiceProviderInterface
{
    public function register(\Phalcon\DiInterface $di)
    {
        $di->setShared(
            'traceClient',
            function () {
                $client = new TraceClient();

                return $client;
            }
        );
    }
}
