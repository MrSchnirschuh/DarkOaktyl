<?php

namespace DarkOak\Exceptions\Service\Database;

use DarkOak\Exceptions\DarkOaktylException;

class DatabaseClientFeatureNotEnabledException extends DarkOaktylException
{
    public function __construct()
    {
        parent::__construct('Client database creation is not enabled in this Panel.');
    }
}


