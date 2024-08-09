<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\Attribute;

use Attribute;

#[Attribute]
class RouteName
{
    public function __construct(public string $name)
    {
    }
}
