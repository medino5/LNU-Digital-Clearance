<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is logged in AND is marked as staff
        if (!$request->user() || !$request->user()->is_staff) {
            return response()->json([
                'message' => 'Unauthorized access. Staff privileges required.'
            ], 403); // 403 means Forbidden
        }

        // If they are staff, let them through to the controller!
        return $next($request);
    }
}