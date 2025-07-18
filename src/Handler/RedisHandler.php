<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Baichuan\Library\Constant\RedisKeyEnum;
use function Hyperf\Config\config;

/**
 * Class RedisHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/01 18:58
 * memo : 待添加：//igbinary_serialize() 時間快，壓縮高
 */
class RedisHandler{

    const INIT = [
        'ttl' => 7200,//單位：秒
    ];

    /**
     * @param callable $func
     * @param string $redisKey
     * @param int $ttl 0/無需緩存，-1/永不過期
     * @return mixed
     * author : zengweitao@gmail.com
     * datetime: 2025/07/13 11:53
     * memo : null
     */
    public static function AutoIgbinaryGet(string $cacheKey, callable $func, int $ttl = self::INIT['ttl'])
    {
        if ($ttl === 0) return $func;
        $redisInstance = redisInstance();
        $value = $redisInstance->get($cacheKey);
        if ($value === false) {
            $value = $func();
            $redisInstance->set($cacheKey, igbinary_serialize($value), ($ttl === -1 ? null/*「null」表示永不過期，詳情參見「set()」*/: $ttl));
            return $value;
        }
        return igbinary_unserialize($value);
    }

    public static function IgbinarySet(string $redisKey, $value, int $ttl = self::INIT['ttl'])
    {
        return redisInstance()->set($redisKey, igbinary_serialize($value), ($ttl === -1 ? null/*「null」表示永不過期，詳情參見「set()」*/: $ttl));
    }

    public static function IgbinaryGet(string $redisKey)
    {
        $value = redisInstance()->get($redisKey);
        if($value === false) return false;
        return igbinary_unserialize($value);
    }

    public static function SingleFlightGroupGet(string $redisKey, callable $func, int $ttl = self::INIT['ttl'], int $waitTime = 180)
    {
        //check[START]
        $Redis = redisInstance();
        $value = $Redis->get($redisKey);
        if ($value !== false) {
            return json_decode($value, true); //則不觸發「try{...}finally{...}」
        }
        //check[END]
        try {
            $owner = uniqid('', true);
            $ttl = ($ttl === -1 ? null : $ttl);
            $md5 = md5($redisKey);
            $mutexRedisKey = RedisKeyEnum::STRING['STRING:MutexName:'] . $md5;
            $resultRedisKey = RedisKeyEnum::LIST['LIST:MutexResult:'] . $md5;
            if ($Redis->set($mutexRedisKey, $owner, ['EX' => $waitTime, 'NX']) === true) {
                $result = $func();
                $resultJson = UtilityHandler::prettyJsonEncode($result);
                //----------
                $Pipe = $Redis->pipeline();
                $Pipe->set($redisKey, $resultJson, $ttl);
                $Pipe->lPush($resultRedisKey, $resultJson); //共享#並發邏輯#返回值
                $Pipe->expire($resultRedisKey, $waitTime);
                $Pipe->exec();
                //----------
            } else {
                if ($resultSlice/* 返回:「含:1鍵名，2鍵值」的索引數組 */ = $Redis->brPop([$resultRedisKey], $waitTime)) { //阻塞，提取#並發邏輯#返回值
                    $result = json_decode($resultSlice[1], true);
                    //----------
                    $Pipe = $Redis->pipeline();
                    $Pipe->lPush($resultRedisKey, $resultSlice[1]);
                    $Pipe->expire($resultRedisKey, $waitTime);
                    $Pipe->exec();
                    //----------
                }
            }
        }  finally {
            if (isset($Redis, $owner, $mutexRedisKey)) {
                //由「$Redis->get($mutexRedisKey)」至「$Redis->del($mutexRedisKey)」的間隙，可能存在並發協程開啟相同的「$mutexRedisKey」任務，因此需要原子操作
                $lua = "return redis.call('get', KEYS[1]) == ARGV[1] and redis.call('del', KEYS[1]) or 0";
                $Redis->eval($lua, [$mutexRedisKey, $owner], 1);
            }
        }
        return $result ?? null;
    }

    /**
     * @param callable $func
     * @param string $redisKey
     * @param int $ttl -1/永不過期
     * @return mixed
     * author : zengweitao@gmail.com
     * datetime: 2023/02/20 11:53
     * memo : null
     */
    public static function autoGet(string $redisKey, callable $func, int $ttl = self::INIT['ttl'])
    {
        $Redis = redisInstance();
        $value = $Redis->get($redisKey);
        if ($value === false) {
            $value = $func();
            $Redis->set($redisKey, UtilityHandler::prettyJsonEncode($value), ($ttl === -1 ? null: $ttl));//null表示永不過期，詳情參見set();
            return $value;
        }
        return json_decode($value, true);
    }

    public static function commonSet(string $redisKey, $value, int $ttl = self::INIT['ttl'])
    {
        $Redis = redisInstance();
        return $Redis->set($redisKey, UtilityHandler::prettyJsonEncode($value), ($ttl === -1 ? null: $ttl));//null表示永不過期，詳情參見set();
    }

    public static function commonGet(string $redisKey)
    {
        $Redis = redisInstance();
        $value = $Redis->get($redisKey);
        if(is_bool($value)) return $value;
        return json_decode($value, true);
    }

    /**
     * @param callable $func
     * @param string $redisKey
     * @param string $hashField
     * @param int $ttl -1/永不過期
     * @return mixed
     * author : zengweitao@gmail.com
     * datetime: 2023/02/20 11:53
     * memo : null
     */
    public static function autoHashGet(string $redisKey, string $hashField, callable $func, int $ttl = self::INIT['ttl'])
    {
        $Redis = redisInstance();
        $value = $Redis->hGet($redisKey, $hashField);
        if ($value === false) {
            $result = $func();
            $value = UtilityHandler::prettyJsonEncode($result);
            $Redis->hSet($redisKey, $hashField, $value);
        }
        //refresh ttl[START]
        if($ttl === -1){
            $Redis->persist($redisKey);
        }else{
            $Redis->expire($redisKey, $ttl);
        }
        //refresh ttl[END]
        return json_decode($value, true);
    }

    /**
     * @param callable $func
     * @param string $mutexName
     * @param int $lockedTime
     * @param bool $returnCacheResult
     * @return array|mixed|null
     * author : zengweitao@gmail.com
     * datetime: 2023/02/20 11:50
     * memo : 互斥鎖
     */
    public static function mutex(string $mutexName, callable $func, int $lockedTime = 3/* , int &$retry = 0 */, bool $returnCacheResult = true)
    {
        try {
            $owner = uniqid('', true);
            $Redis = redisInstance();
            $lockedRedisKey = RedisKeyEnum::STRING['STRING:MutexName:'] . $mutexName;
            $resultRedisKey = RedisKeyEnum::STRING['STRING:MutexResult:'] . $mutexName;
            if ($Redis->set($lockedRedisKey, $owner, ['EX' => $lockedTime, 'NX']) === true) {
                $result = $func();
                if($returnCacheResult) $Redis->lPush($resultRedisKey, UtilityHandler::prettyJsonEncode($result)); // 共享#並發邏輯#返回值
            } elseif($returnCacheResult) {
                if ($result/* 返回:「含:1鍵名，2鍵值」的索引數組 */ = $Redis->brPop([$resultRedisKey], $lockedTime)) {// 阻塞，提取#並發邏輯#返回值
                    $result = json_decode($result[1], true);
                    $Redis->lPush($resultRedisKey, UtilityHandler::prettyJsonEncode($result));
                }
            }
        } finally {
            if (isset($Redis, $owner, $lockedRedisKey, $resultRedisKey) && ($Redis->get($lockedRedisKey) === $owner)) {
                $Redis->expire($resultRedisKey, $lockedTime);
                $Redis->del($lockedRedisKey);
            }
        }
        return $result ?? null;
    }

    //信號量
    public static function semInit()
    {

    }

//    /**
//     * @param string $mutexName
//     * @param callable|null $mainFunc
//     * @param int $lockedTime
//     * @return array|mixed|null
//     * @throws \Throwable
//     * author : zengweitao@msn.com
//     * datetime : 2022-04-17 16:38
//     * memo : 條件變量
//     */
//    static public function pthreadCondInt(string $mutexName, callable $mainFunc = null, int $lockedTime = 3/*, int &$retry = 0*/)
//    {
//        try {
//            //TODO：註冊進程結束函數
//            $owner = uniqid('', true);
//            $Redis = redisInstance();
//            $lockedRedisKey = RedisKeyEnum::STRING['STRING:PthreadCondInt:'] . $mutexName;
//            $resultRedisKey = RedisKeyEnum::STRING['STRING:PthreadCondInt:'] . $mutexName;
//            if ($Redis->set($lockedRedisKey, $owner, ['EX' => $lockedTime, 'NX']) === true) {
//                $result = $mainFunc();
//                $Redis->lPush($resultRedisKey, json_encode($result)); //共享#並發邏輯#返回值
//            } else {
//                if ($result/*返回:「含:1鍵名，2鍵值」的索引數組*/ = $Redis->brPop([$resultRedisKey], $lockedTime)) {//阻塞，提取#並發邏輯#返回值
//                    $result = json_decode($result[1], true);
//                    $Redis->lPush($resultRedisKey, json_encode($result));
//                } else {
//                    //TODO:log
//                    //if($retry) //TODO:限流/重試
//                }
//            }
//        } catch (\Throwable $e) {
//            TraceHandler::sendAlarm2DingTalk($e);
//            throw $e;
//        } finally {
//            if (isset($Redis, $owner, $lockedRedisKey, $resultRedisKey) && ($Redis->get($lockedRedisKey) == $owner)) {
//                $Redis->expire($resultRedisKey, $lockedTime);
//                $Redis->del($lockedRedisKey);
//            }
//        }
//        return $result ?? null;
//    }

//    /**
//     * author : zengweitao@msn.com
//     * datetime : 2022-05-12 17:23
//     * memo : 分佈式lRange()
//     */
//    public function multiLRange(string $queue, int $unitConsumeNum, callable $func): void
//    {
//        try{
//            static $tempQueue = [];
//            $Redis = redisInstance();
//            $slice = $Redis->lRange($queue, 0, $unitConsumeNum - 1);//批量出隊//TODO:操作臨界資源（互斥鎖+重置指針）
//            $tempQueue[] = $slice;
//            if ($slice) {
//                if ($func()) {
//                    $count = count($slice);
//                    $Redis->lTrim($queue, $count, -1);//指定保留元素
//                }
//            }
//        }catch (\Throwable $e){
//            xdebug($e,__FUNCTION__ . 'Throwable');
//        }
//    }

    //隊列管理，支持：1插隊（手動干預優先級）
//    public function queueManager(){}

    public static function matchDelete(string $keyword): array
    {
        $Redis = redisInstance();
        if($cacheList = $Redis->keys("*{$keyword}*")){
            if($cachePrefix = config('redis.default.options.2')){
                array_walk($cacheList,function(&$value/*, $key*/) use($cachePrefix){
                    $value = str_replace($cachePrefix,'',$value);
                });
            }
            $Redis->del(...$cacheList);
        }
        return $cacheList;
    }

    public static function matchList(string $keyword, string $poolName = 'default'): array
    {
        $Redis = redisInstance($poolName);
        if($cacheList = $Redis->keys("*{$keyword}*")){
            if($cachePrefix = config("redis.{$poolName}.options.2")){
                array_walk($cacheList,function(&$value/*, $key*/) use($cachePrefix){
                    $value = str_replace($cachePrefix,'',$value);
                });
            }
        }
        return $cacheList;
    }

    public static function checkRedisKey(){
        //TODO:檢測全局緩存是否重名
    }

    /**
     * author : zengweitao@gmail.com
     * date : 2024-06-10 09:50
     * memo : 冪等請求（idempotent/ˈaɪdəmˌpəʊtənt/），[一般]用於controller
     */
    public static function idemExecute(callable $callable, string $auth = '', int $ttl = 3)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        //$adminInfo = Context::get('adminInfo') ?? [];
        $parameter = (new \ReflectionFunction($callable))->getStaticVariables();
        $unique = md5($auth . json_encode($parameter));
        //example : skeleton:STRING:App\Controller\ApolloController_syncPaddlewaverAdminEnvi_573547d3c28da3f05ff4bee8a5532320
        $redisKey = 'STRING:' . (isset($trace[1]['class'], $trace[1]['function']) ? ("{$trace[1]['class']}_{$trace[1]['function']}_") : ((string)time() . "_")) . $unique;
        return self::autoGet($redisKey, $callable, $ttl);
    }

}
