<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute;

use Closure;
use Haikara\FileBaseRoute\Exception\ActionException;
use Haikara\FileBaseRoute\Attribute\Middleware;
use Haikara\FileBaseRoute\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

class Router implements RequestHandlerInterface {
    protected string $basePath = '';

    protected MiddlewareStack $requestHandler;

    protected Closure $resolver;

    protected Closure $actionInvoker;

    /**
     * @var MiddlewareInterface|class-string<MiddlewareInterface>[]
     */
    protected array $middlewares = [];

    public function __construct(protected string $baseDirectory) {
        $this->requestHandler = new MiddlewareStack();
        $this->resolver = static fn (string $middlewareName) => new $middlewareName();
        $this->actionInvoker = static fn (Closure $action) => $action();
    }

    public function setBasePath(string $basePath): void {
        $this->basePath = $basePath;
    }

    public function setContainer(ContainerInterface $container): void {
        $this->resolver = fn (string $key) => $container->get($key);
    }

    public function setActionInvoker(callable $actionInvoker): void {
        $this->actionInvoker = $actionInvoker;
    }

    /**
     * @param MiddlewareInterface|class-string<MiddlewareInterface> ...$middlewares
     */
    public function addMiddleware(MiddlewareInterface|string ...$middlewares): void {
        $this->middlewares = [...$this->middlewares, ...$middlewares];
    }

    /**
     * @throws ReflectionException
     * @throws ActionException
     * @throws NotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface {
        $explorer = new Explorer($this->basePath, $this->baseDirectory);

        $result = $explorer->explore($request);

        $refFunc = $result['refFunc'];
        $request = $result['request'];

        if ($refFunc === null) {
            throw new NotFoundException();
        }

        $this->requestHandler->setResolver($this->resolver);

        // ActionをMiddlewareStackにセット
        $this->requestHandler->add(new Action($refFunc, $this->actionInvoker));

        // Actionに設定されたMiddlewareをMiddlewareStackにセット
        foreach ($refFunc->getAttributes(Middleware::class) as $refMiddlewareAttr) {
            $middlewareAttr = $refMiddlewareAttr->newInstance();
            $this->requestHandler->add(...$middlewareAttr->middlewares);
        }

        // Routerに設定されたMiddlewareをMiddlewareStackにセット
        $this->requestHandler->add(...$this->middlewares);

        return $this->requestHandler->handle($request);
    }
}
