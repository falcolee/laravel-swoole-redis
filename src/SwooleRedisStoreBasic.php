<?php
/**
 * 功能说明
 * User: falco
 * Date: 4/22/21
 * Time: 5:47 PM
 */

namespace Falcolee\SwooleRedis;

use Illuminate\Cache\RedisStore;
use Swoole\Coroutine\Redis;

class SwooleRedisStoreBasic extends RedisStore
{

    public function __construct()
    {
        $this->redis = new Redis();
        $this->setPrefix(config('cache.prefix'));
        $this->setConnection(config("cache.stores.redis_pool.connection", "default"));
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Predis\ClientInterface
     */
    public function connection()
    {
        // load the config or use the default
        $config = config('database.redis.' . $this->connection, [
            'host'     => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 0,
        ]);
        $this->redis->connect($config['host'], $config['port']);
        if ($config['password']) {
            $this->redis->auth($config['password']);
        }
        return $this->redis;
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  float|int $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        return (bool)$this->connection()->eval(
            $lua, [$this->prefix . $key, $this->serialize($value), (int)max(1, $minutes * 60)], 1
        );
    }
}
