<?php
/*
|----------------------------------------------------------------------------
| TopWindow [ Internet Ecological traffic aggregation and sharing platform ]
|----------------------------------------------------------------------------
| Copyright (c) 2006-2019 http://yangrong1.cn All rights reserved.
|----------------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
|----------------------------------------------------------------------------
| Author: yangrong <yangrong2@gmail.com>
|----------------------------------------------------------------------------
| @Application
|----------------------------------------------------------------------------
*/
namespace Learn\Foundation;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Http\Request;
use Learn\Container;
class Application
{
    /**
     * 容器绑定标识
     * @var array
     */
    protected static $binds = [
        //
        Kernel::class => \App\Http\Kernel::class,
        ConsoleKernel::class => \App\Console\Kernel::class,
        ExceptionHandler::class => \App\Exceptions\Handler::class,
    ];
    /**
     * Creates the Application.
     *
     * @param string|null  $basePath 应用根目录
     * @return \Illuminate\Foundation\Application
     */
    public static function createApplication($basePath = null)
    {
        $basePath = $basePath ?: $_ENV['APP_BASE_PATH'] ?? dirname(__FILE__, 6);
        $laravel = new Container($basePath);
        foreach (static::$binds as $key => $value) {
            $laravel->singleton($key, $value);
        }
        if (is_file($file = $laravel->path('binds.php'))) {
            $singletons = __include_file($file);
            foreach ($singletons as $key => $value) {
                $laravel->singleton($key, $value);
            }
        }
        return $laravel;
    }
    /**
     * Run The Application.
     *
     * @param string|null  $basePath 应用根目录
     * @return void
     */
    public static function run($basePath = null)
    {
        tap(static::createApplication($basePath)->make(Kernel::class), static function ($kernel) {
            if (\is_file($file = $kernel->getApplication()->path('middleware.php'))) {
                $middlewares = \__require_file($file);
                foreach ($middlewares as $middleware) {
                    $kernel->pushMiddleware($middleware);
                }
            }
            $response = $kernel->handle($request = Request::capture());
            $kernel->getApplication()->dispatchEvent(null, [$response]);
            $kernel->terminate($request, $response->send());
        });
    }
    /**
     * Run The Artisan Application.
     *
     * @param string|null  $basePath 应用根目录
     * @return int
     */
    public static function runArtisan($basePath = null)
    {
        return with(static::createApplication($basePath)->make(ConsoleKernel::class), static function ($kernel) {
            $kernel->terminate($input = new ArgvInput(), $status = $kernel->handle($input, new ConsoleOutput()));
            return $status;
        });
    }
}