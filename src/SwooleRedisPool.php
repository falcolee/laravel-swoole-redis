<?php
/**
 * 功能说明
 * User: falco
 * Date: 4/23/21
 * Time: 11:26 AM
 */

namespace Falcolee\SwooleRedis;
use Swoole\Coroutine\Redis;
use Swoole\Coroutine\Channel;
use Illuminate\Support\Facades\Log;

class SwooleRedisPool
{
    protected $pool;

    protected $pushTime = 0;

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

    public function __construct($config,$autoFill=false)
    {
        $this->config = array_merge($this->config, $config);
        $this->pool = new Channel($this->config['poolMax']);
        if ($autoFill){
            $this->fillup();
        }
    }

    public function fillup(){
        while ($this->pool->length() < $this->config['poolMin']){
            $this->pool->push($this->getRedis());
        }
    }

    public function length(){
        return $this->pool->length();
    }

    public function pushTime(){
        return $this->pushTime;
    }

    public function getRedis(){
        $redis = new Redis([
            'connect_timeout' => $this->config['connect_timeout'],
            'timeout'         => $this->config['timeout'],
            'reconnect'       => $this->config['reconnect']
        ]);

        $redis->connect($this->config['host'], $this->config['port']);

        if (!empty($this->config['password'])) {
            $redis->auth($this->config['password']);
        }

        $redis->select($this->config['database']);
        return $redis;
    }

    /**
     * @出池
     */
    public function get()
    {
        $re_i = -1;

        back:
        $re_i++;

        //有空闲连接且连接池处于可用状态
        if ($this->pool->length() > 0) {
            $redis = $this->pool->pop();
        } else {
            //无空闲连接，创建新连接
            $redis = $this->getRedis();
            $this->addPoolTime = time();
        }

        if ($redis->connected === true && $redis->errCode === 0) {
            return $redis;
        } else {
            if ($re_i <= $this->config['retryTimes']) {
                $this->dumpError("redis-重连次数{$re_i}，[errCode：{$redis->errCode}，errMsg：{$redis->errMsg}]");

                $redis->close();
                unset($redis);
                goto back;
            }
            $this->dumpError('Redis重连失败');
        }
    }


    public function put($redis){
        //未超出池最大值时
        if ($this->pool->length() < $this->config['poolMax']) {
            $this->pool->push($redis);
        }
        $this->pushTime = time();
    }

    /**
     * @打印错误信息
     *
     * @param $msg
     */
    public function dumpError($msg)
    {
        Log::error(date('Y-m-d H:i:s', time()) . "：{$msg}");
    }

}