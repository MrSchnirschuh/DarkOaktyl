<?php

namespace Everest\Http\Middleware;

use Illuminate\Http\Request;
use Everest\Exceptions\DisplayException;

class RequireBillingEnabled
{
    /**
     * Handle an incoming request.
     *
     * @throws \Everest\Exceptions\DisplayException
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        if (!config('modules.billing.enabled')) {
            throw new DisplayException('The billing module is not enabled.');
        }

        return $next($request);
    }
}
