<?php
/**
 * 服务注册中心的配置文件。
 *
 * service: http服务端，采集数据使用POST方式发送过去
 * timeout: 连接超时时间，单位 秒，默认 30
 */
return [
    'default' => [
        'timeout' => 30,
        'service' => 'http://10.0.0.1:8000',
    ],
];
