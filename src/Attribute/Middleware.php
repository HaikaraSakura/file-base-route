<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\Attribute;

use Attribute;
use Psr\Http\Server\MiddlewareInterface;

#[Attribute]
class Middleware {

    /**
     * @var class-string<MiddlewareInterface>[] $middlewares
     */
    public array $middlewares;

    /**
     * @param class-string<MiddlewareInterface> ...$middlewares
     */
    public function __construct(string ...$middlewares) {
        $this->middlewares = $middlewares;
    }
}
