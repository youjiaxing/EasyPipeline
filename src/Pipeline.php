<?php
namespace EasyPipeline;

use Psr\Container\ContainerInterface;

class Pipeline
{
    /**
     * @var mixed
     */
    protected $passable;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 每个中间件调用的默认方法
     * @var string
     */
    protected $method = 'handle';

    public function __construct(ContainerInterface $c = null)
    {
        $this->container = $c;
    }

    /**
     * 设置在管道中传递的对象
     *
     * @param mixed $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * 设置管理停留点(中间件)
     *
     * 支持的中间件格式
     *      function ($passable, \Closure $next) {
     *          // do sth.
     *          $result = $next($passable);
     *          // do sth.
     *          return $result;
     *      }
     *
     * @param array|mixed $middleware
     * @return $this
     */
    public function through($middleware)
    {
        $this->middlewares = is_array($middleware) ? $middleware : func_get_args();
        return $this;
    }

    /**
     * 设置中间件的默认调用方法
     *
     * @param $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 传入一个目标闭包, 开启管道
     *
     *
     * @param \Closure $finally
     * @return mixed
     */
    public function then(\Closure $lastPipe)
    {
        $nextPipe = $this->wrapLastPipe($lastPipe);

        foreach (array_reverse($this->middlewares) as $pipe) {
            $nextPipe = function ($passable) use ($nextPipe, $pipe) {
                return $this->callMiddleware($pipe, $passable, $nextPipe);
            };
        }
        return $nextPipe($this->passable);
    }


    /**
     * 对管道中末端管子进行包装
     *
     * 子类可重写该方法, 以支持
     *
     * @param \Closure $pipe
     * @return \Closure
     */
    protected function wrapLastPipe(\Closure $pipe)
    {
        return function ($passable) use ($pipe) {
            return $pipe($passable);
        };
    }

    /**
     * 子类可重写该方法, 以支持更多的 中间件的定义格式
     *
     * 基类默认只支持 闭包格式
     *
     * @param $middleware
     * @param $passable
     * @param $nextPipe
     * @return mixed
     */
    protected function callMiddleware($middleware, $passable, $nextPipe)
    {
        if (is_callable($middleware)) {
            return $middleware($passable, $nextPipe);
        } elseif (is_string($middleware)) {
            $params = [];
            if (false !== $pos = strpos($middleware, '?')) {
                $middleware = substr($middleware, 0, $pos);
                $params = explode(':', substr($middleware, $pos+1));
            }

            $middleware = $this->getContainer()->get($middleware);
            $params = array_merge([$passable, $nextPipe], $params);
        } elseif (is_array($middleware) && count($middleware) == 2 && is_string($middleware[0]) && is_array($middleware[1])) {
            $middleware = $this->getContainer()->get($middleware[0]);
            $params = array_merge([$passable, $nextPipe], $middleware[1]);
        } else {
            $params = [$passable, $nextPipe];
        }

        return method_exists($middleware, $this->method)
            ? call_user_func_array([$middleware, $this->method], $params)
            : call_user_func_array($middleware, $params);
    }

    protected function getContainer()
    {
        return $this->container;
    }
}