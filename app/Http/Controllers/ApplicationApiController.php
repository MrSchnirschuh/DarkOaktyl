<?php

namespace DarkOak\Http\Controllers;

use DarkOak\Http\Controllers\Api\Application\ApplicationApiController as BaseController;

/**
 * Alias for Client controllers that incorrectly imported the wrong namespace.
 * This is a temporary compatibility shim for the JexPanel merge.
 */
class ApplicationApiController extends BaseController
{
}
