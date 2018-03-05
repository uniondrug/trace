# Trace client for uniondrug/framework

分布式调用链跟踪客户端

## 安装

```shell
$ cd project-home
$ composer require uniondrug/trace
$ cp vendor/uniondrug/trace/trace.php config/
```

修改 `app.php` 配置文件，注入服务，服务名称：`traceClient`。

```php
return [
    'default' => [
        ......
        'providers'           => [
            ......
            \Uniondrug\Trace\TraceClientServiceProvider::class,
        ],
    ],
];
```

## 配置

配置文件在 `trace.php` 中，

```php
<?php
/**
 * Trace中心的配置文件。
 *
 * host: 注册中心服务器地址
 * port: 注册中心服务器端口
 * timeout: 连接超时时间，单位 秒，默认 30
 */
return [
    'default' => [
        'server' => 'http://xxxx.xxxx.xxxx/collector', // 或者 'tcp://127.0.0.1:9830'
        'timeout' => 30,
    ],
];
```

## 使用
