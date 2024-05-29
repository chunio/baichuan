<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Baichuan\Library;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            //合併變量
            //打開注釋將導致報錯，待調試[START]
//            'logger' => [
//                'default' => [
//                    //支持「handlers」，參見：https://hyperf.wiki/2.2/#/zh-cn/logger
//                    'handler' => [
//                        //'class' => \Monolog\Handler\RotatingFileHandler::class,
//                        'class' => \Monolog\Handler\StreamHandler::class,
//                        'constructor' => [
//                            'filename' => BASE_PATH . '/runtime/logs/hyperf.log',//日誌文件
//                            'level' => \Monolog\Logger::DEBUG,
//                        ],
//                    ],
//                    'formatter' => [
//                        'class' => \Monolog\Formatter\LineFormatter::class,
//                        'constructor' => [
//                            'format' => null,
//                            'dateFormat' => 'Y-m-d H:i:s',
//                            'allowInlineLineBreaks' => true,
//                            'includeStacktraces' => true,
//                            'ignoreEmptyContextAndExtra' => true,
//                        ],
//                    ],
//                    /*****
//                    'formatter' => [
//                    //'class' => \Monolog\Formatter\JsonFormatter::class,
//                    'class' => Baichuan\Library\Component\Monolog\CustomJsonFormatter::class,
//                    'constructor' => [],
//                    ],
//                    *****/
//                ],
//            ],
            //打開注釋將導致報錯，待調試[END]
            //支持打開注釋，但待優化[START]
//            'dependencies' => [
//                \Hyperf\Contract\StdoutLoggerInterface::class => \Baichuan\Library\Component\Monolog\StdoutLoggerFactory::class,
//            ],
            //支持打開注釋，但待優化[END]
            'processes' => [//
            ],
            'commands' => [
            ],
            'aspects' => [
                \Baichuan\Library\Aspect\MongoAspect::class,
                \Baichuan\Library\Aspect\RequestAspect::class,
                \Baichuan\Library\Aspect\ResponseAspect::class,
                \Baichuan\Library\Aspect\TraceHandlerAspect::class
            ],
            'listeners' => [
            ],
            'annotations' => [
                'scan' => [
                    'collectors' => [
                        //ErrorCodeCollector::class,
                        //WsMiddlewareAnnotationCollector::class,
                    ],
                    'paths' => [
                        __DIR__,
                    ],
                    'class_map' => [
                        // 需映射的類名 => 類所在的文件路徑
                        //\Hyperf\Amqp\ConsumerManager::class => __DIR__ . '/class_map/Hyperf/Amqp/ConsumerManager.php',
                        //\Hyperf\SocketIOServer\Emitter\Emitter::class => __DIR__ . '/class_map/Hyperf/SocketIOServer/Emitter/Emitter.php',
                        //\Mix\Redis\Subscribe\Subscriber::class => __DIR__ . '/class_map/Mix/Redis/Subscribe/Subscriber.php',
                    ],
                ],
            ],
            //複製文件（1目標工程的對應文件已存在時，不會覆蓋，2受限於框架啟動順序，部分基礎配置不適用於publish（如：logger.php））
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'envi',
                    'source' => __DIR__ . '/../publish/baichuan.php',
                    'destination' => BASE_PATH . '/config/autoload/baichuan.php',
                ],
                [
                    'id' => 'config',
                    'description' => 'gotask',
                    'source' => __DIR__ . '/../publish/gotask.php',
                    'destination' => BASE_PATH . '/config/autoload/gotask.php',
                ],
            ],
            'cross' => [
                'button' => true,
                'allow' => [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Headers' => '*',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age' => 86400,
                ],
            ],
            'baichuan' => [
                'monolog' => [
                    'jsonEncodeStatus' => true,
                    'output' => true
                ],
            ]
        ];
    }
}
