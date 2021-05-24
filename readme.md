## laravel swoole redis pool

Laravel package to provide swoole redis pool integration,laravel redis pool cache and session driver. Aims to avoid redis server timeout exception

```$xslt
    public $config = [
        //min 3
        'poolMin'         => 3,
        //max 1000
        'poolMax'         => 64,
        //when lost connection retry
        'retryTimes'      => 2,

        //options config
        'connect_timeout' => 1,
        'timeout'         => 1,
        'reconnect'       => 1
    ];
```

## install
`composer require falcolee/laravel-swoole-redis`

## how to use
 * step 1: make true you've got a right swoole environment  
 * step 2:
add
```
    'redis_pool' => [
        'driver' => 'redis',
        'connection' => 'default',
    ],
```
in your config/cache.php `stores` section below `redis` array

* step 3: change your redis drive or session drive to `redis_pool` in your `.env` file , that is it

