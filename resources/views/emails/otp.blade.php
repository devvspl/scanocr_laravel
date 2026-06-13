<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} Security</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f4; color: #1c1917; margin: 0; padding: 40px 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Header -->
        <div style="background-color: #7f1d1d; padding: 32px 24px; text-align: center;">
            <h2 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">{{ config('app.name') }}</h2>
        </div>

        <!-- Body -->
        <div style="padding: 40px 32px;">
            <h1 style="margin-top: 0; margin-bottom: 16px; font-size: 24px; font-weight: 700; color: #1c1917;">
                @if($type === 'login')
                    Login Verification
                @elseif($type === 'register')
                    Verify Your Registration
                @else
                    Password Reset Request
                @endif
            </h1>
            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #44403c;">
                @if($type === 'login')
                    Welcome back! Enter the following 6-digit code to securely log in to your {{ config('app.name') }} account:
                @elseif($type === 'register')
                    Thank you for joining {{ config('app.name') }}! Enter the following 6-digit code to verify your email and complete your registration:
                @else
                    We received a request to reset your password for your {{ config('app.name') }} account. Enter the following 6-digit code to proceed:
                @endif
            </p>

            <div style="background-color: #fafaf9; border: 1px solid #e7e5e4; border-radius: 8px; padding: 24px; text-align: center; margin-bottom: 32px;">
                <div style="font-size: 36px; font-weight: 800; letter-spacing: 12px; color: #7f1d1d; margin: 0;">
                    {{ $otp }}
                </div>
            </div>

            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.6; color: #78716c;">
                @if($type === 'login' || $type === 'register')
                    If you did not request this OTP, you can safely ignore this email.
                @else
                    If you did not request a password reset, please ignore this email or contact support if you have concerns.
                @endif
            </p>
        </div>

        <!-- Footer -->
        <div style="background-color: #f5f5f4; padding: 24px; text-align: center; border-top: 1px solid #e7e5e4;">
            <p style="margin: 0; font-size: 12px; color: #a8a29e;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                <a href="{{ route('privacy') }}" style="color: #7f1d1d; text-decoration: none;">Privacy Policy</a> &bull; <a href="{{ route('terms') }}" style="color: #7f1d1d; text-decoration: none;">Terms of Service</a>
            </p>
        </div>

    </div>
</body>
</html>
