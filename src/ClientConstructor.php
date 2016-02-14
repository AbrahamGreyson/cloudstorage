<?php

namespace CloudStorage;

use CloudStorage\Credentials\CredentialsInterface;
use CloudStorage\Exceptions\CloudStorageException;

class ClientConstructor
{
    private $arguments;

    private static $typeMap = [
        'resource' => 'is_resource',
        'callable' => 'is_callable',
        'int'      => 'is_int',
        'bool'     => 'is_bool',
        'string'   => 'is_string',
        'object'   => 'is_object',
        'array'    => 'is_array',
    ];

    private static $defaultArguments = [
        'service'           => [
            'type'     => 'value',
            'valid'    => ['string'],
            'required' => true,
            'internal' => true,
            'doc'      => '初始化的云服务名称。通过对应的云服务客户端使用 CloudStorage 时，这个值是默认自动填充的（如：Cloudstorage\\Upyun\\UpyunClient）。',
        ],
        'exceptionClass'    => [
            'type'     => 'value',
            'valid'    => ['string'],
            'default'  => CloudStorageException::class,
            'internal' => true,
            'doc'      => '报错时使用的异常类。',
        ],
        'scheme'            => [
            'type'    => 'value',
            'valid'   => ['string'],
            'default' => 'https',
            'doc'     => '连接云服务时使用的 URI 模式。CloudStorage 将默认启用 https（如 SSL/TLS 连接）连接云服务的端点。你可以通过设置 ``scheme`` 为 http 不加密的连接云服务，但是极不推荐。',
        ],
        'signatureProvider' => [
            'type'    => 'value',
            'value'   => ['callable'],
            'default' => [__CLASS__, 'applyDefaultSignatureProvider'],
            'doc'     => '签名提供者。一个 callable 类型的函数，接受云服务名称（如 upyun）和签名版本（例如 base）为参数，并返回 SignatureInterface 对象或 null。这个签名提供者被客户端用来创建签名。CloudStorage\\Signature\\SignatureProvider 中列举了一组内置的提供者。',
        ],
        'signatureVersion'  => [
            'type'    => 'config',
            'valid'   => ['string'],
            'default' => [__CLASS__, 'applyDefaultSignatureVersion'],
            'doc'     => '代表一个云服务的自定义签名版本的字符串（如 base）。注意：不同操作的签名版本可能会覆盖这个默认版本。',
        ],
        'profile'           => [
            'type'  => 'config',
            'valid' => ['string'],
            'fn'    => [__CLASS__, 'applyProfile'],
            'doc'   => '当云服务凭证是从配置文件中创建的，指定使用哪一个身份。这个设置会覆盖 CLOUDSTORAGE_PROFILE 环境变量。注意：指定 profile 会导致 credentials 设置中的内容被忽略。',
        ],
        'credentials'       => [
            'type'  => 'value',
            'valid' => [
                CredentialsInterface::class, 'array', 'bool',
                'callable'],
            'fn'    => [__CLASS__, 'applyDefaultProvider'],
            'doc'   => '指定用来签名请求的凭证。可以提供
            CloudStorage\\Credentials\\CredentialsInterface 对象，一个包含
            key，secret 的关联数组，`false` 作为空凭证，或一个 callable 凭证提供者创建凭证或返回 null。CloudStorage\\Credentials\\CredentialProvider 中列举了一组内置的凭证提供者。如果没有提供凭证，CloudStorage 将试图从环境变量中加载它们。',
        ],
        'retries'           => [
            'type'    => 'value',
            'valid'   => ['int'],
            'fn'      => [__CLASS__, 'applyRetries'],
            'default' => 3,
            'doc'     => '客户端最大重试次数（传入 0 禁用重试）。',
        ],
        'validate'          => [
            'type'    => 'value',
            'valid'   => ['bool', 'array'],
            'default' => true,
            'fn'      => [__CLASS__, 'applyValidate'],
            'doc'     => '设为 `false` 禁用客户端参数验证。设为 `true` 使用默认的验证约束。设为一个关联数组去弃用特殊的验证约束。',
        ],
        'debug'             => [
            'type'  => 'value',
            'valid' => ['bool', 'array'],
            'fn'    => [__CLASS__, 'applyDebug'],
            'doc'   => '设为 `true` 时在客户端向云服务发送请求时显示调试信息。此外，你还可以提供一个关联数组，含有以下键名 —— logfn：（callable）随日志信息一起调用的函数；stream_size：（int）当流数据的尺寸大于此数字时，流数据将不会被记录（设为 0 禁止所有流数据的记录）；scrub_auth：（bool）设为 `false` 禁用从日志信息中筛选授权信息；http：（bool）设为 `false` 禁用更底层 HTTP 适配器的调试特性（例如 curl 的详细信息输出）。',
        ],
        'http'              => [
            'type'  => 'value',
            'valid' => ['array'],
            'doc'   => '设置一个关联数组作为 CloudStorage 客户端每个请求的选项（例如 proxy，verify 等）。',
        ],
        'httpHandler'       => [
            'type' => 'value',
            'valid' => ['callable'],
            'fn' => [__CLASS__, 'applyHttpHandler'],
            'doc' => 'HTTP 处理器是一个函数，接受 PSR-7 请求对象作为参数，返回一个 promise， 代表已完成的 PSR-7 响应对象或已失败的包含异常数据的数组。注意：这个选项将覆盖任何已有的 handler 选项。',
        ],
        'handler' => [
            'type' => 'value',
            'valid' => ['callable'],
            'fn' => [__CLASS__, 'applyHandler'],
            'default' => [__CLASS__, 'applyDefaultHandler'],
            'doc' => '处理器，接受 CloudStorage\\Contracts\\CommandInterface 和
            PSR-7 请求对象作为参数，返回一个 promise， 代表已完成的
            CloudStorage\\Contracts\\ResultInterface 对象或已失败的
            CloudStorage\\Exceptions\\CloudStorageException
            。处理器并不接收下一个处理器，因为其是最终的，用来完成一个命令的函数。如果没有提供处理器，则使用默认的 Guzzle 处理器。',
        ],
    ];

}