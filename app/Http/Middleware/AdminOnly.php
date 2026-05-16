<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Hanya pengguna dengan peran admin yang boleh melanjutkan request API.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Aksi ini terbatas untuk administrator.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
