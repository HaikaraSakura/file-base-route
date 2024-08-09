<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\RequestMethod;

use Attribute;

#[Attribute]
class Get implements RequestMethodInterface
{
    public function equals(string $methodName): bool {
        return strtoupper($methodName) === 'GET';
    }
}
