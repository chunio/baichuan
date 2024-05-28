<?php

declare(strict_types=1);

namespace Baichuan\Library\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Resource\Json\JsonResource;
use Hyperf\Resource\Response\Response;
use function Hyperf\Config\config;

/**
 * @Aspect
 * Class ResponseAspect
 * @package Component\Hyperf\Aspect
 * author : zengweitao@gmail.com
 * datetime: 2023/02/21 20:35
 * memo : 修復JsonResource無法輸出null/bool/float/integer/string/...
 */
class TraceHandlerAspect extends AbstractAspect
{

    public array $classes = [
        'Baichuan\Library\Handler\TraceHandler::*',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        /** @var Response $instance */
        if(config('baichuan.LOG_STATUS')){
            return $proceedingJoinPoint->process();
        }else{
            return [];
        }
    }

}
