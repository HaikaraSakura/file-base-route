<?php

namespace Haikara\FileBaseRoute;

use Psr\Http\Message\ServerRequestInterface;

class RouteProvider
{
    private array $context;

    protected function __construct(
        ServerRequestInterface $request
    ) {
        $this->context = $request->getAttribute(Router::class);
    }

    public static function createFromRequest(ServerRequestInterface $request): static
    {
        return new static($request);
    }

    public function getArgs(): array
    {
        return $this->context['args'] ?? [];
    }
}