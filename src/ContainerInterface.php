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
*/
declare (strict_types=1);

namespace Learn;

use Illuminate\Contracts\Foundation\Application;

interface ContainerInterface extends Application
{
    /**
     * 获取容器中的对象实例 不存在则创建
     * 
     * @param string     $abstract    类名或者标识
     * @param array|true $vars        变量
     * @return object
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public static function pull(string $abstract, array $vars = []);
	
    /**
     * 实例化给定类型的具体实例
     * 
     * @param  \Closure|string  类名、类标识或者闭包
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function build($concrete);
	
    /**
     * 执行函数或者闭包方法 支持参数调用
     * 
     * @param string|Closure $function 函数或者闭包
     * @param array          $vars     参数
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function invokeFunction($function, array $vars = []);
	
    /**
     * 调用反射执行类的方法 支持参数绑定
     * 
     * @param mixed $method     方法
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function invokeMethod($method, array $vars = [], bool $accessible = false);
	
    /**
     * 调用反射执行类的方法 支持参数绑定
     * 
     * @param object $instance 对象实例
     * @param mixed  $reflect  反射类
     * @param array  $vars     参数
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function invokeReflectMethod($instance, $reflect, array $vars = []);
	
    /**
     * 调用反射执行callable 支持参数绑定
     * 
     * @param mixed $callable
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function invoke($callable, array $vars = [], bool $accessible = false);
	
    /**
     * 调用反射执行类的实例化 支持依赖注入
     * 
     * @param string $class 类名
     * @param array  $vars  参数
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function invokeClass(string $class, array $vars = []);
	
    /**
     * 创建工厂对象实例
     *
     * @param string $name      工厂类名
     * @param string $namespace 默认命名空间
     * @param array  $args
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public static function createFactory(string $name, string $namespace = '', ...$args);
	
    /**
     * 添加事件句柄
     * 
     * @param string   $event      要添加的事件名称
     * @param \Closure $function   事件触发时执行的函数(函数的第一个参数必须是\Illuminate\Http\Response对象实例或其子类)
     * @param bool     $useCapture 指定事件是否在捕获或冒泡阶段执行（false事件句柄在冒泡阶段执行）
     * @return void
     */
    public function addEventListener(string $event, \Closure $function, bool $useCapture = false);
	
    /**
     * 执行事件分发调度
     * 
     * @param string $event 指定事件名称
     * @param array  $vars  参数
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function dispatchEvent($event = null, array $vars = []);
	
    /**
     * 移除由 addEventListener() 方法添加的事件句柄
     * 
     * @param  string $event 要移除的事件名称
     * @return void
     */
    public function removeEventListener($event, \Closure $function = null, bool $useCapture = false);
}