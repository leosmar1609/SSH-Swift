<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->is_admin) {
            $message = 'Acesso restrito a administradores.';

            // Explorer calls these routes via axios and reads {success, message}
            // to show a toast — an HTML 403 page would just be a generic failure.
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
