<?php

namespace Access\Exception;

use Access\Exception;

class ClosedConnectionException extends Exception
{
    public function __construct()
    {
        parent::__construct('Connection is closed');
    }
}
