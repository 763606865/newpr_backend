<?php

namespace App\Libs\IM\Drivers;

use App\Libs\IM\LoggerMiddleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

abstract class AbstractDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Return raw config array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->config['end_point'] ?? '', '/');
    }

    public function httpRequest(string $method, string $path, array $options = []): Response
    {
        $base = $this->getBaseUrl();
        $url = $base ? ($base.'/'.ltrim($path, '/')) : $path;

        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $options['headers'] ?? []);

        $loggerMiddleware = new LoggerMiddleware(app('log'));

        $client = Http::withHeaders($headers)->withMiddleware($loggerMiddleware)->timeout($options['timeout'] ?? 10);

        $method = strtoupper($method);

        try {
            if ($method === 'GET') {
                $response = $client->get($url, $options['query'] ?? []);
            } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                if (array_key_exists('json', $options)) {
                    $response = $client->{$method}($url, $options['json']);
                } elseif (array_key_exists('form_params', $options)) {
                    $response = $client->{$method}($url, $options['form_params']);
                } else {
                    $response = $client->{$method}($url, $options);
                }
            } else {
                $response = $client->send($method, $url, $options);
            }
        } catch (ConnectionException $e) {
            throw $e;
        }

        return $response;
    }

    /**
     * @param string $path
     * @param array $options
     * @return Response
     * @throws ConnectionException
     */
    public function get(string $path, array $options = []): Response
    {
        return $this->httpRequest('GET', $path, $options);
    }

    /**
     * @param string $path
     * @param array $options
     * @return Response
     * @throws ConnectionException
     */
    public function post(string $path, array $options = []): Response
    {
        return $this->httpRequest('POST', $path, $options);
    }

    /**
     * @param string $path
     * @param array $options
     * @return Response
     * @throws ConnectionException
     */
    public function put(string $path, array $options = []): Response
    {
        return $this->httpRequest('PUT', $path, $options);
    }

    /**
     * @param string $path
     * @param array $options
     * @return Response
     * @throws ConnectionException
     */
    public function delete(string $path, array $options = []): Response
    {
        return $this->httpRequest('DELETE', $path, $options);
    }

    /**
     * @param string $path
     * @param array $options
     * @return Response
     * @throws ConnectionException
     */
    public function patch(string $path, array $options = []): Response
    {
        return $this->httpRequest('PATCH', $path, $options);
    }

    /**
     * @param string $path
     * @param array $options
     * @return Response
     * @throws ConnectionException
     */
    public function options(string $path, array $options = []): Response
    {
        return $this->httpRequest('OPTIONS', $path, $options);
    }

    /**
     * Generic API resolver.
     * Maps a name like 'user' to a concrete method on the driver (userApi()).
     *
     * @param string $name
     * @return mixed
     */
    public function api(string $name)
    {
        $method = lcfirst($name) . 'Api';
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        throw new \BadMethodCallException("API '{$name}' not implemented for driver " . static::class);
    }

    public function getProvider()
    {
        return static::class;
    }
}
