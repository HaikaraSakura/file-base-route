<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\RequestMethod;

enum ContentType: string
{
    case Form = 'application/x-www-form-urlencoded';
    case Json = 'application/json';
};
