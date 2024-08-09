<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\RequestMethod;

interface RequestMethodInterface {
    public function equals(string $methodName): bool;
}
