<?php

declare(strict_types=1);

namespace CommerceBird\Dependencies\League\Container\Argument;

interface ArgumentInterface
{
    /**
     * @return mixed
     */
    public function getValue();
}
