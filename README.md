# EasyPipeline
一个简单管道的实现, 参考Laravel的管道实现.

# 使用
`(new EasyPipeline\Pipeline())->send($passable)->through($pipes)->then($lastPipe);`

若将管道用于web请求的中间件组合
```php
$response = (new EasyPipeline\Pipeline())
	->send($request)
	->through($middlewares)
	->then($dispatch);
```

# 中间件的定义方式

```php
$middlewares = [
	VerifyCsrfToken::class,	// 类名
	
	[ThrottleRequests::class, [60, 1]],	// 数组形式: 类名 + 数组参数
	
	ThrottleRequests::class."?60:1",	// 字符串形式: 类名 + 参数
	
	"throttle?60:1",	// 字符串别名形式: 管理实例化时需传入容器实例
	
	function ($request, \Closure $next) {	// 闭包
		//...
		return $next($request);
	},
];
```

使用非闭包方式定义的中间件时, 需注入容器(实现ContainerInterface)以便Pipeline实例化中间件.

若指定某个类, 则该类需实现 'handle'方法 或 '__invoke'方法.