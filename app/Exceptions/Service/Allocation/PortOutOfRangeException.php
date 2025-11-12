<?php

namespace DarkOak\Exceptions\Service\Allocation;

use DarkOak\Exceptions\DisplayException;

class PortOutOfRangeException extends DisplayException
{
    /**
     * PortOutOfRangeException constructor.
     */
    public function __construct()
    {
        parent::__construct(trans('exceptions.allocations.port_out_of_range'));
    }
}

