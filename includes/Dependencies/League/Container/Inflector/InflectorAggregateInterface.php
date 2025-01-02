<?php

declare(strict_types=1);

namespace CommerceBird\Dependencies\League\Container\Inflector;

use IteratorAggregate;
use CommerceBird\Dependencies\League\Container\ContainerAwareInterface;

interface InflectorAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(string $type, ?callable $callback = null): Inflector;
    public function inflect(object $object);
}
