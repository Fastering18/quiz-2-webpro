<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Only allow authenticated users with the admin role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (($request->user()?->role) !== 'admin') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya administrator yang dapat mengakses endpoint ini.',
            ], 403);
        }

        return $next($request);
    }
}
