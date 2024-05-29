# Baichuan
## Installation

### step 1    
```
docker run -d --privileged --name php8370environment -v /sys/fs/cgroup:/sys/fs/cgroup:ro -v /windows:/windows --tmpfs /run --tmpfs /run/lock -p 9501:9501 chunio/php:8370 /sbin/init
docker exec -it php8370environment /bin/bash
```
### step 2
```
TODO: archive hyperf3.1
composer create-project hyperf/hyperf-skeleton
```
### step 3
vi ./hyperf-skeleton/composer.json
```json
{
  "name": "hyperf/hyperf-skeleton",
  "type": "project",
  "keywords": [
    "php",
    "swoole",
    "framework",
    "hyperf",
    "microservice",
    "middleware"
  ],
  "description": "A coroutine framework that focuses on hyperspeed and flexible, specifically use for build microservices and middlewares.",
  "license": "Apache-2.0",
  "require": {
    "php": ">=8.1",
    "hyperf/cache": "~3.1.0",
    "hyperf/command": "~3.1.0",
    "hyperf/config": "~3.1.0",
    "hyperf/db-connection": "~3.1.0",
    "hyperf/engine": "^2.10",
    "hyperf/framework": "~3.1.0",
    "hyperf/guzzle": "~3.1.0",
    "hyperf/http-server": "~3.1.0",
    "hyperf/logger": "~3.1.0",
    "hyperf/memory": "~3.1.0",
    "hyperf/process": "~3.1.0",
    "hyperf/database": "~3.1.0",
    "hyperf/redis": "~3.1.0",
    "hyperf/config-apollo": "~3.1.0",
    "hyperf/constants": "~3.1.0",
    "hyperf/amqp": "~3.1.0",
    "hyperf/elasticsearch": "~3.1.0",
    "hyperf/tracer": "~3.1.0",
    "baichuan/library": "dev-main"
  },
  "require-dev": {
    "friendsofphp/php-cs-fixer": "^3.0",
    "hyperf/devtool": "~3.1.0",
    "hyperf/testing": "~3.1.0",
    "mockery/mockery": "^1.0",
    "phpstan/phpstan": "^1.0",
    "swoole/ide-helper": "^5.0"
  },
  "suggest": {
    "ext-openssl": "Required to use HTTPS.",
    "ext-json": "Required to use JSON.",
    "ext-pdo": "Required to use MySQL Client.",
    "ext-pdo_mysql": "Required to use MySQL Client.",
    "ext-redis": "Required to use Redis Client."
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    },
    "files": []
  },
  "autoload-dev": {
    "psr-4": {
      "HyperfTest\\": "./test/"
    }
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "optimize-autoloader": true,
    "sort-packages": true
  },
  "extra": [],
  "scripts": {
    "post-root-package-install": [
      "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
    ],
    "post-install-cmd": [
      "php ./bin/hyperf.php vendor:publish baichuan/library"
    ],
    "post-update-cmd": [
      "php ./bin/hyperf.php vendor:publish baichuan/library"
    ],
    "post-autoload-dump": [
      "rm -rf runtime/container"
    ],
    "test": "co-phpunit --prepend test/bootstrap.php --colors=always",
    "cs-fix": "php-cs-fixer fix $1",
    "analyse": "phpstan analyse --memory-limit 300M",
    "start": [
      "Composer\\Config::disableProcessTimeout",
      "php ./bin/hyperf.php start"
    ]
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:chunio/baichuan.git"
    }
  ]
}
```
composer update

### step 4 / 调整配置
vi ./hyperf-skeleton/config/autoload/baichuan.php
```php
<?php
# example
declare(strict_types=1);
return [
    // 發佈至「工程目錄」的config/autoload/baichuan.php
    'LOG_STATUS' => 0,//TODO:是否開啟百川日誌（未完全實現「開啟/關閉」控制（返回值非布爾類型的仍會觸發，待完善）：TraceHandlerAspect >> TraceHandler），值：0否，1是
    'traceHandlerStatus' => 1,//是否開啟鏈路跟蹤
    'monologHandlerJsonEncodeStatus' => 1,//是否單行，值：0否，1是
    'monologHandlerOutput' => 1,//是否輸出至控制台，值：0否，1是
];
```
```php
//vi ./hyperf-skeleton/config/autoload/config_center.php
// 'enable' => (bool) env('CONFIG_CENTER_ENABLE', false) //關閉配置中心（如：apollo）
```

### step 5 

```php
<?php
// vi ./hyperf-skeleton/app/Controller/AbstractController.php
declare(strict_types=1);
namespace App\Controller;
abstract class AbstractController extends \Baichuan\Library\Component\Controller\AbstractController
{
}
```
```php
<?php
// vi ./hyperf-skeleton/app/Controller/ExampleController.php
declare(strict_types=1);
namespace App\Controller;
use App\Logic\ExampleLogic;
use Baichuan\Library\Component\Resource\JsonResource;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\RequestMapping;
use function Hyperf\Support\make;
/**
 * @AutoController()
 */
class ExampleController extends AbstractController
{
    /**
     * @RequestMapping(path="update", methods="get,post")
     * @return JsonResource
     */
    public function index()
    {
        // make(ExampleLogic::class)->index()
        return $this->success(1);
    }
}
```
```php
<?php
//vi ./hyperf-skeleton/config/routes.php
declare(strict_types=1);
/**
 * This file is part of Hyperf.
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;
Router::addRoute(['GET', 'POST'], '/example/index', 'App\Controller\ExampleController@index');
Router::get('/favicon.ico', function () {
    return '';
});
```
