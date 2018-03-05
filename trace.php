<?php
/**
 * 服务注册中心的配置文件。
 *
 * service 或者 host/port 必须二选一
 * service: http服务端，采集数据使用POST方式发送过去
 * host: TCP方式注册中心服务器地址
 * port: TCP注册中心服务器端口
 * timeout: 连接超时时间，单位 秒，默认 30
 */
return [
    'default' => [
        'timeout' => 30,
        'service' => 'http://10.0.0.1:8000/collector',
        'host'    => '127.0.0.1',
        'port'    => 9530,
    ],
];
