<?php

declare(strict_types=1);

namespace CommerceBird\Dependencies\League\Container\Exception;

use CommerceBird\Dependencies\Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
