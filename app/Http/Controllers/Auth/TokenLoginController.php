<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenLoginController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Entry point — GET or POST /token-login?token=<jwt>[&employeeid=<id>]
    // ─────────────────────────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $token = $request->input('token');

        if (empty($token)) {
            return $this->fail($request, 'Token is required.');
        }

        // ── Decode & verify ───────────────────────────────────────────────
        $secretKey = env('JWT_TOKEN_LOGIN_SECRET');

        if (empty($secretKey)) {
            return $this->fail($request, 'Token login is not configured on this server.');
        }

        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        } catch (ExpiredException $e) {
            return $this->fail($request, 'Token has expired.');
        } catch (SignatureInvalidException $e) {
            return $this->fail($request, 'Token signature is invalid.');
        } catch (BeforeValidException $e) {
            return $this->fail($request, 'Token is not yet valid.');
        } catch (\Exception $e) {
            return $this->fail($request, 'Token is malformed or unreadable.');
        }

        // ── Validate sub claim ────────────────────────────────────────────
        $sub = $decoded->sub ?? null;

        if (empty($sub)) {
            return $this->fail($request, 'Token payload is missing the required sub claim.');
        }
        // ── Find active user ──────────────────────────────────────────────
        $user = User::where('employee_id', $sub)
            ->where('is_active', 1)
            ->first();

        if (! $user) {
            return $this->fail($request, 'No active user found for this token.');
        }

        // ── Session conflict guard ────────────────────────────────────────
        if (Auth::check() && Auth::id() !== $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // ── Log in ────────────────────────────────────────────────────────
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fail helper — JSON for XHR, redirect for browser
    // ─────────────────────────────────────────────────────────────────────────

    private function fail(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 401);
        }

        return redirect()->route('login')->withErrors(['token' => $message]);
    }
}
