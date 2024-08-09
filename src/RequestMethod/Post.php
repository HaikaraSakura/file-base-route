<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\RequestMethod;

use Attribute;

#[Attribute]
class Post implements RequestMethodInterface
{
    public function __construct(
        public ContentType $contentType = ContentType::Form
    ) {
    }

    public function equals(string $methodName): bool {
        return strtoupper($methodName) === 'POST';
    }
}
