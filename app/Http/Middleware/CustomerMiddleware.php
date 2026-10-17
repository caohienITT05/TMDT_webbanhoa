<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user();

        if (! $customer) {
            return redirect()->guest(route('login'));
        }

        abort_unless($customer->role === 'customer' && $customer->is_active, 403);

        return $next($request);
    }
}
