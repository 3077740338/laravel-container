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
| 容器管理类 支持PSR-11
|----------------------------------------------------------------------------
*/
declare (strict_types = 1);

namespace Learn;

use Illuminate\Support\Str;
use Illuminate\Foundation\Application;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;

class Container extends Application implements ContainerInterface
{
    /**
     * All of the event callbacks by class type.
     *
     * @var array[]
     */
    protected $eventCallbacks = [];
	
    /**
     * Object Oriented
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        parent::__construct($basePath);

        static::setInstance($this);

        $this->instance('app', $this);

        foreach ([
            'app' => [
                static::class,
                ContainerInterface::class,
                IlluminateContainer::class,
                Application::class,
            ],
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }
	
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
    public static function pull(string $abstract, array $vars = [])
    {
        return static::getInstance()->make($abstract, $vars);
    }
	
    /**
     * 实例化给定类型的具体实例
     * 
     * @param  \Closure|string  类名、类标识或者闭包
     * @return mixed
	 *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    public function build($concrete)
    {
        if ($concrete instanceof \Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        $this->buildStack[] = $concrete;

        try {
            $object = $this->invokeClass($concrete, $this->getLastParameterOverride());
        } catch (BindingResolutionException | \InvalidArgumentException $e) {
            array_pop($this->buildStack);

            throw $e;
        }

        array_pop($this->buildStack);

        return $object;
    }
		
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
    public function invokeFunction($function, array $vars = [])
    {
        try {
            $reflect = new \ReflectionFunction($function);
        } catch (\ReflectionException $e) {
            throw new BindingResolutionException(sprintf('function not exists: %s()', $function), 0, $e);
        }

        $args = $this->bindParams($reflect, $vars);

        return $function(...$args);
    }

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
    public function invokeMethod($method, array $vars = [], bool $accessible = false)
    {
        if (is_array($method)) {
            [$class, $method] = $method;

            $class = is_object($class) ? $class : $this->invokeClass($class);
        } else {
            // 静态方法
            [$class, $method] = explode('::', $method);
        }

        try {
            $reflect = new \ReflectionMethod($class, $method);
        } catch (\ReflectionException $e) {
            $class = is_object($class) ? get_class($class) : $class;
            throw new BindingResolutionException(sprintf('method not exists: %s::%s()', $class, $method), 0, $e);
        }

        $args = $this->bindParams($reflect, $vars);

        if ($accessible) {
            $reflect->setAccessible($accessible);
        }

        return $reflect->invokeArgs(is_object($class) ? $class : null, $args);
    }

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
    public function invokeReflectMethod($instance, $reflect, array $vars = [])
    {
        $args = $this->bindParams($reflect, $vars);

        return $reflect->invokeArgs($instance, $args);
    }

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
    public function invoke($callable, array $vars = [], bool $accessible = false)
    {
        if ($callable instanceof \Closure) {
            return $this->invokeFunction($callable, $vars);
        } elseif (is_string($callable) && false === strpos($callable, '::')) {
            return $this->invokeFunction($callable, $vars);
        } else {
            return $this->invokeMethod($callable, $vars, $accessible);
        }
    }

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
    public function invokeClass(string $class, array $vars = [])
    {
        try {
            $reflect = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new BindingResolutionException(sprintf('class not exists: %s', $class), 0, $e);
        }
		
        if (! $reflect->isInstantiable()) {
            return $this->notInstantiable($class);
        }
		
        if ($reflect->hasMethod('__invokeClass')) {
            $method = $reflect->getMethod('__invokeClass');
            if ($method->isPublic() && $method->isStatic()) {
                $args   = $this->bindParams($method, $vars);
                $object = $method->invokeArgs(null, $args);
                return $object;
            }
        }

        $constructor = $reflect->getConstructor();

        $args = $constructor ? $this->bindParams($constructor, $vars) : [];

        $object = $reflect->newInstanceArgs($args);

        return $object;
    }

    /**
     * 绑定参数
     * 
     * @param ReflectionFunctionAbstract $reflect 反射类
     * @param array                      $vars    参数
     * @return array
	 *
     * @throws \InvalidArgumentException
     */
    protected function bindParams(\ReflectionFunctionAbstract $reflect, array $vars = [])
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type   = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();
        $args   = [];

        foreach ($params as $param) {
            $name           = $param->getName();
            $lowerName      = Str::snake($name);
            $reflectionType = $param->getType();

            if ($reflectionType && $reflectionType->isBuiltin() === false) {
				$args[] = $this->getObjectParam($reflectionType->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && array_key_exists($name, $vars)) {
                $args[] = $vars[$name];
            } elseif (0 == $type && array_key_exists($lowerName, $vars)) {
                $args[] = $vars[$lowerName];
            } elseif (!is_null($concrete = $this->getContextualConcrete('$'.$name))) {
                $args[] = $concrete instanceof \Closure ? $concrete($this) : $concrete;
            } elseif($param->isDefaultValueAvailable()){
                $args[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(sprintf('method param miss: %s', $name));
            }
        }

        return $args;
    }

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
    public static function createFactory(string $name, string $namespace = '', ...$args)
    {
        $class = false !== strpos($name, '\\') ? $name : $namespace . ucwords($name);

        return static::getInstance()->invokeClass($class, $args);
    }

    /**
     * 获取对象类型的参数值
     * 
     * @param string $className 类名
     * @param array  $vars      参数
     * @return mixed
	 *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \InvalidArgumentException
     */
    protected function getObjectParam(string $className, array &$vars)
    {
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = $this->resolve($className);
        }

        return $result;
    }
	
    /**
     * 添加事件句柄
     * 
     * @param string   $event      要添加的事件名称
     * @param \Closure $function   事件触发时执行的函数(函数的第一个参数必须是\Illuminate\Http\Response对象实例或其子类)
     * @param bool     $useCapture 指定事件是否在捕获或冒泡阶段执行（false事件句柄在冒泡阶段执行）
     * @return void
     */
    public function addEventListener(string $event, \Closure $function, bool $useCapture = false)
    {
        $this->eventCallbacks[$event] = $function;
    }
	
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
    public function dispatchEvent($event = null, array $vars = [])
    {
        if (is_null($event)) {
            foreach ($this->eventCallbacks as $_event => $function) {
                $this->dispatchEvent($_event, $vars);
            }
            return;
        }
		
        try {
            return $this->invokeFunction($this->eventCallbacks[$event], $vars);
        } catch (\Exception $e) {
            throw new BindingResolutionException(sprintf('event not exists: %s', $event), 0, $e);
        }
    }
	
    /**
     * 移除由 addEventListener() 方法添加的事件句柄
     * 
     * @param  string $event 要移除的事件名称
     * @return void
     */
    public function removeEventListener($event, \Closure $function = null, bool $useCapture = false)
    {
        if (isset($this->eventCallbacks[$event])) {
            unset($this->eventCallbacks[$event]);
        }
    }
}
