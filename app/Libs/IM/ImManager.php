<?php

namespace App\Libs\IM;

use App\Libs\IM\Drivers\Custom;
use App\Libs\IM\Drivers\Easemob;
use App\Libs\IM\Drivers\RongCloud;
use App\Libs\IM\Drivers\Tencent;
use InvalidArgumentException;

class ImManager
{
    protected $app;

    protected array $config;

    protected array $connections = [];

    public function __construct($app)
    {
        $this->app = $app;
        $this->config = (array) config('im');
    }

    /**
     * Get a connection instance by name
     */
    public function connection(?string $name = null)
    {
        $name = $name ?? ($this->config['default'] ?? 'custom');

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $this->connections[$name] = $this->makeConnection($name);

        return $this->connections[$name];
    }

    protected function makeConnection(string $name)
    {
        $connections = $this->config;
        $driverConfig = $connections[$name] ?? null;

        if (! is_array($driverConfig)) {
            throw new InvalidArgumentException("IM driver [{$name}] is not configured.");
        }

        $map = [
            'tencent' => Tencent::class,
            'rongcloud' => RongCloud::class,
            'easemob' => Easemob::class,
            'custom' => Custom::class,
        ];

        $class = $map[$name] ?? null;
        if ($class === null) {
            throw new InvalidArgumentException("IM driver [{$name}] is not supported.");
        }

        return new $class($driverConfig);
    }

    /**
     * Alias for connection() — convenience method used by Im::connect(...)
     */
    public function connect(?string $name = null)
    {
        return $this->connection($name);
    }

    /**
     * Shortcut to call default connection
     */
    public function __call($method, $arguments)
    {
        return $this->connection()->{$method}(...$arguments);
    }
}
