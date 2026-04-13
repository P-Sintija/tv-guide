<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('auth.basic_auth.username');
        $password = config('auth.basic_auth.password');

        if (
            !$this->credentialsConfigured($username, $password)
            || !$this->isValidAuthorization($request, $username, $password)
        ) {
            return response()->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    private function credentialsConfigured(string $username, string $password): bool
    {
        return $username && $password;
    }

    private function isValidAuthorization(Request $request, string $username, string $password): bool
    {
        $authHeader = $request->header('Authorization');
        $encodedCredentials = base64_encode("{$username}:{$password}");

        return $authHeader === 'Basic ' . $encodedCredentials;
    }
}
