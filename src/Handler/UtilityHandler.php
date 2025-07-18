<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Baichuan\Library\Constant\AsciiEnum;
use Baichuan\Library\Constant\RedisKeyEnum;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Hyperf\Context\ApplicationContext;
use Hyperf\Redis\Redis;
use function Hyperf\Support\env;

/**
 * Class UtilityHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/01 13:50
 */
class UtilityHandler
{

    public static function pagination(array $list, int $pageIndex, int $pageSize): array
    {
        $recordNum = count($list);
        $pageLimit = intval(ceil($recordNum / $pageSize));
        if ($pageIndex < 1) {
            $pageIndex = 1;
        } elseif ($pageIndex > $pageLimit && $pageLimit != 0 ) {
            $pageIndex = $pageLimit;
        }
        $start = intval(($pageIndex - 1) * $pageSize);
        $currentList = $list ? array_slice($list, $start, $pageSize) : [];
        return [
            'current_list' => $currentList,
            'page_index' => $pageIndex,
            'page_size' => $pageSize,
            'page_limit' => $pageLimit,
            'record_num' => $recordNum,
        ];
    }

    public static function order(array $array, string $slaveField, string $sort = 'DESC'): array
    {
        $newArray = $valueArray = [];
        foreach ($array as $key => $value) {
            $valueArray[$key] = $value[$slaveField];
        }
        if (strtoupper($sort) === 'ASC') {
            asort($valueArray);
        } else {
            arsort($valueArray);
        }
        reset($valueArray);
        foreach ($valueArray as $key => $value) {
            $newArray[$key] = $array[$key];
        }
        return array_values($newArray);
    }

    public static function commonHttpRequest(
        $method,
        $uri,
        $query,
        array $body = [],
        array $header = ['Content-Type' => 'application/json'],
        array $cookie = [],
        int $timeout = 10
    ): array
    {
        $option = [
            'timeout' => $timeout,
            'headers' => $header
        ];
        if($query) $option['query'] = $query;
        if($header['Content-Type'] === 'application/json' && $body) $option['json'] = $body;
        if($cookie) $option['cookies'] = CookieJar::fromArray($cookie['detail'], $cookie['domain']);
        $stream = (new Client())->request(strtoupper($method), $uri, $option)->getBody();
        return json_decode((string)$stream,true) ?? [];
    }

    public static function commonHttpPost(string $uri, array $body = [], $header = ['Content-Type' => 'application/json'], array $cookieDetail = [], string $cookieDomain = '')
    {
        try{
            $config = [
                'timeout' => 3,
                'headers' => $header,
                'json' => $body,
            ];
            if($cookieDetail && $cookieDomain){
                $config['cookies'] = CookieJar::fromArray($cookieDetail, $cookieDomain);
            }
            $client = new \GuzzleHttp\Client($config);
            $body = json_decode((string)$client->request('POST', $uri, $config)->getBody(), true);
            $return = [
                'status' => true,
                'body' => $body
            ];
        }catch (\Throwable $e){
            // 異常原因：1連接超時，...
            $return = [
                'status' => false,
                'body' => $e->getMessage()
            ];
        }
        return $return;
    }

    public static function commonHttpGet(string $uri, array $query = [], array $cookieDetail = [], string $cookieDomain = '', int $timeout = 5, int $ttl = 0/*緩存時間*/)
    {
        try{
            $redisKey = RedisKeyEnum::STRING['STRING:CommonHttpGet:'] . md5(igbinary_serialize([$uri, $query, $cookieDetail, $cookieDomain]));
            if ($ttl && ($return = RedisHandler::commonGet($redisKey))) return $return;
            $config = [
                //TODO：[開啟報錯]NOTICE Socket::ssl_verify() (ERRNO 1014): can not verify peer from fd#17 with error#20: unable to get local issuer certificate
                //'verify' => true
                'verify' => false,
                'query' => $query,
                'timeout' => $timeout,
            ];
            if($cookieDetail && $cookieDomain){
                $config['cookies'] = CookieJar::fromArray($cookieDetail, $cookieDomain);
            }
            $client = new \GuzzleHttp\Client($config);
            $body = json_decode((string)$client->request('GET', $uri, $config)->getBody(), true);
            $return = [
                'status' => true,
                'body' => $body
            ];
            if ($ttl) RedisHandler::commonSet($redisKey, $return, $ttl);
        }catch (\Throwable $e){
            // 異常原因：1連接超時，...
            $return = [
                'status' => false,
                'body' => $e->getMessage()
            ];
        }
        return $return;
    }

    public static function prettyJsonEncode($object, ?int $flag = JSON_PRETTY_PRINT): string
    {
        //JSON_PRETTY_PRINT//易讀格式（即：自動換行）
        $flagCounter = JSON_UNESCAPED_SLASHES/*不轉義反斜杠*/ | JSON_UNESCAPED_UNICODE/*unicode轉至中文*/;
        if (!$flag) {
            $flagCounter |= $flag;
        }
        return json_encode($object, $flagCounter);
    }

    public static function filterControlCharacter(string $string)
    {
        if(!$string) return '';
        $format = '';
        for($i = 0; isset($string[$i]); $i++) {
            $asciiCode = ord($string[$i]);
            if($asciiCode <= 31 || $asciiCode == 127){
                $format .= '[' . AsciiEnum::CONTROL_CHARACTER[$asciiCode] . ']';
            }elseif($asciiCode > 31){
                $format .= $string[$i];
            }
        }
        return trim($format);
    }

    public static function matchEnvi(string $envi): bool
    {
        return env('APP_ENV') == $envi;
    }

    /**
     * @param mixed $key 如key等於null，則取$parameter裡第一個非null值；如key不等於null，則取第一個$parameter($key)或$parameter[$key]的非null值
     * @param mixed ...$parameter closure || 數組
     * @return null|array|mixed
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:45
     * memo : 順序檢索第一个非null值
     */
    public static function matchNonNullValue(string $key, ...$parameter)
    {
        foreach ($parameter as $member) {
            if (is_null($member)) {
                continue;
            }
            if ($member instanceof Closure) {
                $value = $member($key);
            } else {
                $value = null == $key ? value($member) : data_get($member, $key);
            }
            if (!is_null($value)) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @param null $id
     * @return mixed|\Psr\Container\ContainerInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:51
     * memo : null
     */
    public static function di($id = null)
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }
        return $container;
    }

    /**
     * @param string $type enum: day, week, month, latest7
     * @param string $status 返回樣式，值：date/日期（示例：2023-01-01），timestamp/時間戳（示例：1687536000）
     * author : zengweitao@gmail.com
     * datetime: 2023/06/30 15:02
     * memo : null
     */
    public static function pullDatePeriod(string $type = 'day', string $status = 'date'): array
    {
        $currentTimestamp = time();
        switch ($type){
            case 'day':
                $latestPeriodStart = $latestPeriodEnd = date("Y-m-d", strtotime("yesterday"));
                $currentPeriodStart = $currentPeriodEnd = date("Y-m-d", $currentTimestamp);
                break;
            case 'week':
                $latestPeriodStart = date("Y-m-d", strtotime("last week monday"));;
                $latestPeriodEnd =date("Y-m-d", strtotime("last week sunday"));;
                $currentPeriodStart = date("Y-m-d", strtotime("this week monday"));
                $currentPeriodEnd = date('Y-m-d', $currentTimestamp);
                break;
            case 'month':
                $latestPeriodStart = date("Y-m-01", strtotime("last month"));
                $latestPeriodEnd = date("Y-m-t", strtotime("last month"));
                $currentPeriodStart = date("Y-m-01", $currentTimestamp);
                $currentPeriodEnd = date('Y-m-d', $currentTimestamp);
                break;
            case 'latest7'://含：今天
            default:
                $latestPeriodStart = date("Y-m-d", strtotime("-13 days"));
                $latestPeriodEnd = date("Y-m-d", strtotime("-7 days"));
                $currentPeriodStart = date("Y-m-d", strtotime("-6 days"));
                $currentPeriodEnd = date('Y-m-d', $currentTimestamp);
                break;
        }
        return [
            'current' => [
                $status == 'timestamp' ? strtotime("$currentPeriodStart 00:00:00") : $currentPeriodStart,
                $status == 'timestamp' ? strtotime("$currentPeriodEnd 23:59:59") : $currentPeriodEnd,
            ],
            'latest' => [
                $status == 'timestamp' ? strtotime("$latestPeriodStart 00:00:00") : $latestPeriodStart,
                $status == 'timestamp' ? strtotime("$latestPeriodEnd 23:59:59") : $latestPeriodEnd,
            ]
        ];
    }

}