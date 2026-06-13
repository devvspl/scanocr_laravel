<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f4; color: #1c1917; margin: 0; padding: 40px 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        
        <!-- Header -->
        <div style="background-color: #7f1d1d; padding: 40px 24px; text-align: center;">
            <h2 style="margin: 0 0 8px; color: #ffffff; font-size: 32px; font-weight: 800; letter-spacing: -0.5px;">{{ config('app.name') }}</h2>
            <p style="margin: 0; color: #fca5a5; font-size: 16px;">Unleash Your Reading Potential</p>
        </div>

        <!-- Body -->
        <div style="padding: 40px 32px;">
            <h1 style="margin-top: 0; margin-bottom: 16px; font-size: 24px; font-weight: 700; color: #1c1917;">
                Welcome to the pack, {{ explode(' ', $user->name)[0] }}! 🐺
            </h1>
            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #44403c;">
                You've successfully verified your email and joined the {{ config('app.name') }} community! Your ultimate journey into discovering, logging, and organizing your favorite books starts right here.
            </p>
            
            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ route('dashboard') }}" style="display: inline-block; background-color: #7f1d1d; color: #ffffff; text-decoration: none; padding: 16px 36px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(127, 29, 29, 0.2);">
                    Go to Your Dashboard
                </a>
            </div>

            <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #44403c;">
                We're incredibly excited to have you on board. Start tracking your recent reads immediately to map out your journey!
            </p>
            <p style="margin: 24px 0 0; font-size: 16px; line-height: 1.6; color: #44403c;">
                Best regards,<br>
                <strong>The {{ config('app.name') }} Team</strong>
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
