<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Closure;
use SplStack;

class MiddlewareStack implements RequestHandlerInterface
{
    /**
     * @var SplStack
     */
    protected SplStack $middlewares;

    protected Closure $resolver;

    public function __construct()
    {
        $this->middlewares = new SplStack();
        $this->resolver =
            static fn (MiddlewareInterface|RequestHandlerInterface|string $middleware) => is_string($middleware) ? new $middleware(): $middleware;
    }

    public function setResolver(callable $resolver): void
    {
        $this->resolver = $resolver(...);
    }

    /**
     * @param MiddlewareInterface|RequestHandlerInterface|class-string<MiddlewareInterface|RequestHandlerInterface> ...$middlewares
     * @return $this
     */
    public function add(MiddlewareInterface|RequestHandlerInterface|string ...$middlewares): static
    {
        foreach ($middlewares as $middleware) {
            $this->middlewares->push($middleware);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->middlewares->pop();

        if (is_callable($middleware)) {
            return $middleware($request, $this);
        }

        if (is_string($middleware)) {
            $middleware = ($this->resolver)($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        } elseif ($middleware instanceof RequestHandlerInterface) {
            return $middleware->handle($request);
        }
    }
}
