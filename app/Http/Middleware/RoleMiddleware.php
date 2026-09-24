<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        abort_unless(
            $user && in_array($user->role, User::ROLES, true) && in_array($user->role, $roles, true),
            403,
        );

        return $next($request);
    }
}