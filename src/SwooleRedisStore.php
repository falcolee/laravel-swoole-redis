<?php
/**
 * 功能说明
 * User: falco
 * Date: 4/22/21
 * Time: 5:47 PM
 */

namespace Falcolee\SwooleRedis;

use Illuminate\Cache\RedisStore;

class SwooleRedisStore extends RedisStore
{

    /**
     * @var RedisPoolManager
     */
    protected $redis;

    public $config = [];

    /**
     * SwooleRedisStore constructor.
     * @param RedisPoolManager $redis
     * @param string $prefix
     * @param string $connection
     * return void
     */
    public function __construct(RedisPoolManager $redis, $prefix = '', $connection = 'default')
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);
        $this->setConnection($connection);
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

        $result = (bool)$this->connection()->eval(
            $lua, [$this->prefix . $key, $this->serialize($value), (int)max(1, $minutes * 60)], 1
        );
        return $result;
    }
}
