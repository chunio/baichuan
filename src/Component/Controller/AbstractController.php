<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Controller;

use Baichuan\Library\Component\MultiTrait\ResponseTrait;
use Baichuan\Library\Component\MultiTrait\ValidateTrait;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;

/**
 * 抽象控制器
 * Class AbstractController
 */
abstract class AbstractController
{

    use ResponseTrait;
    use ValidateTrait;

    /**
     * @Inject
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     *
     * @var ResponseInterface
     */
    protected $response;

}
