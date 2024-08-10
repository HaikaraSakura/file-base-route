<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute;

use Haikara\FileBaseRoute\RequestMethod\RequestMethodInterface;
use Haikara\FileBaseRoute\Exception\MethodNotAllowedException;
use Closure;
use ReflectionAttribute;
use ReflectionFunction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class Action implements RequestHandlerInterface {
    public function __construct(
        protected ReflectionFunction $refFunc,
        protected Closure $actionInvoker
    ) {
    }

    /**
     * @throws MethodNotAllowedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface {
        $refMethodAttrs = $this->refFunc->getAttributes(
            RequestMethodInterface::class,
            ReflectionAttribute::IS_INSTANCEOF
        ) ?? [];

        // RequestMethod属性がなければ判定なしで実行
        if (count($refMethodAttrs) === 0) {
            return ($this->actionInvoker)($this->refFunc->getClosure(), $request);
        }

        $method = $request->getMethod();

        // 合致するRequestMethod属性があれば実行
        foreach ($refMethodAttrs as $refMethodAttr) {
            $methodAttr = $refMethodAttr->newInstance();

            if ($methodAttr->equals($method)) {
                return ($this->actionInvoker)($this->refFunc->getClosure(), $request);
            }
        }

        throw new MethodNotAllowedException();
    }
}
