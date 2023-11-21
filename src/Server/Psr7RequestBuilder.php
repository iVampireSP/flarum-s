<?php
declare(strict_types=1);

namespace FlarumS\Server;

use Swoole\Http\Request as SwooleRequest;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;

/**
 * Class Psr7RequestBuilder
 * @package FlarumS\Server
 * @author <trint.dev@gmail.com>
 */
class Psr7RequestBuilder
{
    public function build(SwooleRequest $swooleRequest)
    {
        $rawContent = (string) $swooleRequest->rawcontent();

        $body = new Stream('php://memory', 'r+b');
        $body->write($rawContent);

        return new ServerRequest(
            $this->buildServerParams($swooleRequest),
            $swooleRequest->files ?? [],
            $swooleRequest->server['request_uri'] ?? null,
            $swooleRequest->server['request_method'] ?? null,
            $body,
            $swooleRequest->header ?? [],
            $swooleRequest->cookie ?? [],
            $swooleRequest->get ?? [],
            $swooleRequest->post ?? null,
            $swooleRequest->server['server_protocol'] ?? '1.1'
        );
    }

    public function buildServerParams(SwooleRequest $swooleRequest)
    {
        $server = $swooleRequest->server ?? [];
        $header = $swooleRequest->header ?? [];

        $user = get_current_user();
        if (function_exists('posix_getpwuid')) {
            $user = posix_getpwuid(posix_geteuid())['name'];
        }

        $script_name   = '/swoole-httpd';
        $document_root = '';

        return [
            'USER'                           => $user,
            'HTTP_CACHE_CONTROL'             => $header['cache-control'] ?? '',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => $header['upgrade-insecure-requests-control'] ?? '',
            'HTTP_CONNECTION'                => $header['connection'] ?? '',
            'HTTP_DNT'                       => $header['dnt'] ?? '',
            'HTTP_ACCEPT_ENCODING'           => $header['accept-encoding'] ?? '',
            'HTTP_ACCEPT_LANGUAGE'           => $header['accept-accept-language'] ?? '',
            'HTTP_ACCEPT'                    => $header['accept'] ?? '',
            'HTTP_USER_AGENT'                => $header['user-agent'] ?? '',
            'HTTP_HOST'                      => $header['user-host'] ?? '',
            'SERVER_NAME'                    => 'flarumS',
            'SERVER_PORT'                    => $server['server_port'] ?? null,
            'SERVER_ADDR'                    => $server['server_addr'] ?? '',
            'REMOTE_PORT'                    => $server['remote_port'] ?? null,
            'REMOTE_ADDR'                    => $server['remote_addr'] ?? '',
            'SERVER_SOFTWARE'                => $server['server_software'] ?? '',
            'GATEWAY_INTERFACE'              => $server['server_software'] ?? '',
            'REQUEST_SCHEME'                 => 'http',
            'SERVER_PROTOCOL'                => $server['server_protocol'] ?? null,
            'DOCUMENT_ROOT'                  => $document_root,
            'DOCUMENT_URI'                   => '/',
            'REQUEST_URI'                    => $server['request_uri'] ?? '',
            'SCRIPT_NAME'                    => $script_name,
            'CONTENT_LENGTH'                 => $header['content-length'] ?? null,
            'CONTENT_TYPE'                   => $header['content-type'] ?? null,
            'REQUEST_METHOD'                 => $server['request_method'] ?? 'GET',
            'QUERY_STRING'                   => $server['query_string'] ?? '',
            'SCRIPT_FILENAME'                => rtrim($document_root, '/').'/'.ltrim($script_name),
            'PATH_INFO'                      => $server['path_info'] ?? '',
            'FCGI_ROLE'                      => 'RESPONDER',
            'PHP_SELF'                       => $server['path_info'] ?? '',
            'REQUEST_TIME_FLOAT'             => $server['request_time_float'] ?? '',
            'REQUEST_TIME'                   => $server['request_time'] ?? '',
        ];
    }
}
