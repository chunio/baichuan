<?php

declare(strict_types=1);

use Baichuan\Library\Handler\ModelHandler;
use Baichuan\Library\Handler\MongoDBHandler;
use Baichuan\Library\Handler\MonologHandler;
use Baichuan\Library\Handler\TraceHandler;
use Baichuan\Library\Handler\UtilityHandler;
use Hyperf\Kafka\ProducerManager;
use Hyperf\Redis\RedisFactory;
use function Hyperf\Support\make;

//if (!function_exists('traceHandler')) {
//    function traceHandler($variable, string $label = '', string $level = 'info', $monolog = true): bool
//    {
//        if($monolog && class_exists(MonologHandler::class)){
//            MonologHandler::$level($variable, $label);
//        }else{
//            //非協程I/O[START]
//            $path = BASE_PATH . "/runtime/logs/" . __FUNCTION__ . "-0000-00-" . date("d") . ".log";//keep it for one month
//            if (!file_exists($path)) touch($path);//compatible file_put_contents() cannot be created automatically
//            $trace = TraceHandler::traceFormatter($variable, $label);
//            if (abs(filesize($path)) > 1024 * 1024 * 1024) {//flush beyond the limit/1024m
//                file_put_contents($path, $trace/*, LOCK_EX*/); //TODO:阻塞風險
//            } else {
//                file_put_contents($path, $trace, FILE_APPEND/* | LOCK_EX*/);
//            }
//            if(UtilityHandler::matchEnvi('local')) echo "$trace\n";
//            //非協程I/O[END]
//        }
//        return true;
//    }
//}

if (!function_exists('modelHandler')) {
    function modelHandler(string $model): ModelHandler
    {
        return new ModelHandler($model);
    }
}

if (!function_exists('mongoDBHandler')) {
    function mongoDBHandler(string $collection, string $db = ''): MongoDBHandler
    {
        return make(MongoDBHandler::class, [$collection, $db]);
    }
}

if (!function_exists('redisInstance')) {
    function redisInstance(string $poolName = 'default'): Hyperf\Redis\Redis
    {
        return UtilityHandler::di()->get(RedisFactory::class)->get($poolName);
    }
}

if (!function_exists('kafkaInstance')) {
    function kafkaInstance(string $poolName = 'default'): Hyperf\Kafka\Producer
    {
        return UtilityHandler::di()->get(ProducerManager::class)->getProducer($poolName);
    }
}

if (!function_exists('ParseVersion')) {
    /**
     * @parameter : --
     * @return : --
     * author : zengweitao@gmail.com
     * date : 2024-06-12 14:59
     * memo : [示例]1.0.0 >> [1,0,0]
     */
    function ParseVersion(string $version): array
    {
        $array = explode('.', $version);
        $result = [];
        foreach ($array as $part) {
            $result[] = intval($part);
        }
        return $result;
    }
}

if (!function_exists('CompareVersion')) {
    /**
     * @parameter : --
     * @return : -1 ：v1 < v2，0 ：v1 == v2，1 ：v1 > v2
     * author : zengweitao@gmail.com
     * date : 2024-06-12 14:47
     * memo :
     */
    function CompareVersion(string $v1, string $v2) {
        $version1 = ParseVersion($v1);
        $version2 = ParseVersion($v2);
        $maxLength = max(count($version1), count($version2));
        for ($i = 0; $i < $maxLength; $i++) {
            $part1 = $version1[$i] ?? 0;
            $part2 = $version2[$i] ?? 0;
            if ($part1 < $part2) {
                return -1;
            } elseif ($part1 > $part2) {
                return 1;
            }
        }
        return 0;
    }
}