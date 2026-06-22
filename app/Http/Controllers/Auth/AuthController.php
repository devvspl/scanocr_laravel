<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\LoginLog;
use App\Models\User;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Keep them unverified until OTP is solved
        $otp = rand(100000, 999999);
        Cache::put('register_otp_' . $user->email, $otp, now()->addMinutes(15));
        
        Mail::to($user->email)->send(new OtpMail($otp, 'register'));

        return redirect()->route('register.verify.form')->with(['email' => $user->email, 'success' => 'Registration initiated! Please enter the OTP sent to your email.']);
    }

    public function showVerifyRegisterForm(Request $request)
    {
        $email = session('email', $request->query('email'));
        if (!$email) return redirect()->route('register');
        
        return view('auth.otp-challenge', [
            'email' => $email, 
            'action' => route('register.verify.submit'),
            'title' => 'Verify Registration'
        ]);
    }

    public function verifyRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
        ]);

        $cachedOtp = Cache::get('register_otp_' . $request->email);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return back()->with('error', 'Invalid or expired OTP.')->withInput(['email' => $request->email]);
        }

        $user = User::where('email', $request->email)->first();

        // Mark verified and send welcome email if not already verified
        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
            Mail::to($user->email)->send(new WelcomeMail($user));
        }

        Cache::forget('register_otp_' . $request->email);

        Auth::login($user);
        $this->bootstrapSession($user, request());

        return redirect($this->getRedirectRoute($user))->with('success', 'Registration verified successfully! Welcome to ScanOCR.');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::validate($credentials)) {
            $user = User::where('email', $request->email)->first();

            // Intercept if they are logging in but haven't verified their registration OTP yet
            if (is_null($user->email_verified_at)) {
                $otp = rand(100000, 999999);
                Cache::put('register_otp_' . $user->email, $otp, now()->addMinutes(15));
                
                Mail::to($user->email)->send(new OtpMail($otp, 'register'));
                
                return redirect()->route('register.verify.form')->with([
                    'email' => $user->email, 
                    'error' => 'You must verify your registration before logging in! A fresh verification OTP has been sent.'
                ]);
            }

            $remember = $request->boolean('remember');

            // ── OTP gate — controlled by LOGIN_OTP_ENABLED in .env ────────
            if (config('auth.login_otp_enabled', true)) {
                $otp = rand(100000, 999999);

                Cache::put('login_otp_' . $request->email, [
                    'otp'      => $otp,
                    'remember' => $remember,
                ], now()->addMinutes(15));

                Mail::to($request->email)->send(new OtpMail($otp, 'login'));

                return redirect()->route('login.verify.form')->with([
                    'email'   => $request->email,
                    'success' => 'Please enter the OTP sent to your email to complete login.',
                ]);
            }

            // ── Direct login — OTP disabled ───────────────────────────────
            Auth::login($user, $remember);
            $request->session()->regenerate();
            $this->bootstrapSession($user, $request);

            return redirect()->intended($this->getRedirectRoute($user))->with('success', 'Logged in successfully!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showVerifyLoginForm(Request $request)
    {
        $email = session('email', $request->query('email'));
        if (!$email) return redirect()->route('login');
        
        return view('auth.otp-challenge', [
            'email' => $email, 
            'action' => route('login.verify.submit'),
            'title' => 'Verify Login'
        ]);
    }

    public function verifyLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
        ]);

        $cached = Cache::get('login_otp_' . $request->email);

        if (!$cached || $cached['otp'] != $request->otp) {
            return back()->with('error', 'Invalid or expired OTP.')->withInput(['email' => $request->email]);
        }

        $user = User::where('email', $request->email)->first();
        Auth::login($user, $cached['remember']);

        Cache::forget('login_otp_' . $request->email);
        $request->session()->regenerate();
        $this->bootstrapSession($user, $request);

        return redirect()->intended($this->getRedirectRoute($user))->with('success', 'Logged in successfully!');
    }

    public function logout(Request $request)
    {
        LoginLog::recordLogout(Auth::id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'You have been logged out.');
    }

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email'], [
            'email.exists' => 'We could not find an account with that email address.'
        ]);
        
        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->email, $otp, now()->addMinutes(15));

        Mail::to($request->email)->send(new OtpMail($otp, 'reset'));

        return redirect()->route('password.reset')->with(['email' => $request->email, 'success' => 'An OTP has been sent to your email.']);
    }

    public function showVerifyForm(Request $request)
    {
        $email = session('email', $request->query('email'));
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'Please submit your email first.');
        }
        return view('auth.verify-otp', compact('email'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $cachedOtp = Cache::get('otp_' . $request->email);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return back()->with('error', 'Invalid or expired OTP.')->withInput(['email' => $request->email]);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        Cache::forget('otp_' . $request->email);

        return redirect()->route('login')->with('success', 'Password reset successfully. You can now securely login.');
    }

    public function resendLoginOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate new OTP
        $otp = rand(100000, 999999);
        
        // Get existing remember preference or default to false
        $existingCache = Cache::get('login_otp_' . $request->email);
        $remember = $existingCache['remember'] ?? false;
        
        Cache::put('login_otp_' . $request->email, [
            'otp' => $otp,
            'remember' => $remember
        ], now()->addMinutes(15));
        
        Mail::to($request->email)->send(new OtpMail($otp, 'login'));
        
        return back()->with('success', 'A new OTP has been sent to your email.');
    }

    public function resendRegisterOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $otp = rand(100000, 999999);
        Cache::put('register_otp_' . $request->email, $otp, now()->addMinutes(15));
        
        Mail::to($request->email)->send(new OtpMail($otp, 'register'));
        
        return back()->with('success', 'A new OTP has been sent to your email.');
    }

    public function resendPasswordResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->email, $otp, now()->addMinutes(15));
        
        Mail::to($request->email)->send(new OtpMail($otp, 'reset'));
        
        return back()->with('success', 'A new OTP has been sent to your email.');
    }

    /**
     * Set the correct active company for this user's session and record the login.
     *
     * The company is resolved as:
     *  1. The user's first explicitly allowed company (from UserCompanyAccess)
     *  2. Fallback: the global default company
     *
     * This ensures users never land on a company they don't have access to,
     * regardless of what was stored in a previous session.
     */
    private function bootstrapSession(User $user, Request $request): void
    {
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowed      = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);

        // ── Restore company ───────────────────────────────────────────────────
        // Priority: last explicitly chosen company (if still allowed) → first allowed → global default
        $companyId = null;

        if ($user->last_company_id) {
            // Verify the saved company is still in the user's allowed list
            $stillAllowed = $allowed->firstWhere('id', $user->last_company_id);
            if ($stillAllowed) {
                $companyId = $user->last_company_id;
            }
        }

        if (! $companyId) {
            $company   = $allowed->first() ?? Company::where('is_default', true)->where('is_active', true)->first();
            $companyId = $company?->id;
        }

        if ($companyId) {
            Company::setForSession($companyId);
        }

        // ── Restore financial year ────────────────────────────────────────────
        // Priority: last explicitly chosen FY (if it still exists) → global current
        if ($user->last_fy_id && FinancialYear::find($user->last_fy_id)) {
            FinancialYear::setForSession($user->last_fy_id);
        }
        // If no saved FY, the existing getCurrent() fallback in FinancialYear handles it automatically

        // ── Record login activity ─────────────────────────────────────────────
        LoginLog::recordLogin(
            $user,
            $companyId,
            $request->ip() ?? 'unknown',
            $request->userAgent() ?? ''
        );
    }

    /**
     * Determine the appropriate landing page for a user based on their roles.
     *
     * Rules:
     *  - Super Admin                               → dashboard (always)
     *  - Exactly ONE workflow role and no others   → that role's dedicated page
     *  - Multiple roles of any kind                → dashboard (they need full navigation)
     *  - Any other single non-workflow role        → dashboard (fallback)
     */
    private function getRedirectRoute(User $user): string
    {
        // Super Admin always lands on the dashboard regardless of other roles
        if ($user->hasRole('Super Admin')) {
            return route('dashboard');
        }

        $workflowRoleMap = [
            'Temp Scanning'   => route('workflow.temp-scan.index'),
            'Direct Scanning' => route('workflow.direct-scan.index'),
            'Super Scanner'   => route('workflow.super-scanner.index'),
            'Bill Approval'   => route('workflow.bill-approval.index'),
            'Classification'  => route('workflow.classification.index'),
        ];

        // Collect which workflow roles this user actually has
        $assignedWorkflowRoles = array_values(array_filter(
            array_keys($workflowRoleMap),
            fn($role) => $user->hasRole($role)
        ));

        // Only redirect away from dashboard when the user has exactly one role
        // total and it is a workflow role — multi-role users need full navigation
        if ($user->roles->count() === 1 && count($assignedWorkflowRoles) === 1) {
            return $workflowRoleMap[$assignedWorkflowRoles[0]];
        }

        return route('dashboard');
    }
}
