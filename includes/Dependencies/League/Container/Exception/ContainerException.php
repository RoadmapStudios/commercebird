<?php

declare(strict_types=1);

namespace CommerceBird\Dependencies\League\Container\Exception;

use CommerceBird\Dependencies\Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
