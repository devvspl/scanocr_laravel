<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class ApiJwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token not provided.'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key(config('app.key'), 'HS256'));
            $user = User::find($decoded->sub);

            if (!$user || !$user->is_active) {
                return response()->json(['success' => false, 'message' => 'Invalid or inactive user.'], 401);
            }

            auth()->login($user);
            $request->setUserResolver(fn() => $user);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['success' => false, 'message' => 'Token expired.'], 401);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid token.'], 401);
        }

        return $next($request);
    }
}
