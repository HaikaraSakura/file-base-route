<?php

declare(strict_types=1);

namespace Haikara\FileBaseRoute\Exception;

use Exception;

class NotFoundException extends Exception implements RoutingExceptionInterface
{
}
