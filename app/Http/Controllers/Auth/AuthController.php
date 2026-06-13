<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
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

        return redirect()->route('dashboard')->with('success', 'Registration verified successfully! Welcome to WolfBooks.');
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

            // Normal login flow
            $otp = rand(100000, 999999);
            $remember = $request->boolean('remember');
            
            Cache::put('login_otp_' . $request->email, [
                'otp' => $otp,
                'remember' => $remember
            ], now()->addMinutes(15));
            
            Mail::to($request->email)->send(new OtpMail($otp, 'login'));
            
            return redirect()->route('login.verify.form')->with(['email' => $request->email, 'success' => 'Please enter the OTP sent to your email to complete login.']);
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

        return redirect()->intended(route('dashboard'))->with('success', 'Logged in successfully!');
    }

    public function logout(Request $request)
    {
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
}
