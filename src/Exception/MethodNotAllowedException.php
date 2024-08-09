<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\Exception;

use Exception;

class MethodNotAllowedException extends Exception implements RoutingExceptionInterface
{
}
