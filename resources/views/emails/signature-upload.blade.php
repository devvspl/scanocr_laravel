<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Your Digital Signature</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f4; color: #1c1917; margin: 0; padding: 40px 0;">
    <div style="max-width: 520px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">

        <div style="background-color: #7f1d1d; padding: 24px; text-align: center;">
            <h2 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 800;">{{ config('app.name') }}</h2>
            <p style="margin: 6px 0 0; color: #fecaca; font-size: 12px;">Digital Signature Request</p>
        </div>

        <div style="padding: 32px 28px;">
            <p style="margin: 0 0 8px; font-size: 15px; color: #44403c;">
                Hi <strong>{{ $user->name }}</strong>,
            </p>
            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.6; color: #57534e;">
                You have been requested to upload your digital signature. This signature will be used for document approvals.
            </p>

            <div style="text-align: center; margin: 28px 0;">
                <a href="{{ $signUrl }}" style="display: inline-block; background-color: #7f1d1d; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 14px 36px; border-radius: 8px;">
                    ✍️ Upload / Draw Signature
                </a>
            </div>

            <p style="margin: 0 0 8px; font-size: 13px; color: #78716c; text-align: center;">
                You can draw your signature or upload an image.
            </p>

            <div style="border-top: 1px solid #e7e5e4; padding-top: 16px; margin-top: 24px;">
                <p style="margin: 0; font-size: 11px; color: #a8a29e;">
                    This link is valid for 72 hours. If you did not expect this request, please ignore this email.
                </p>
            </div>
        </div>

        <div style="background-color: #f5f5f4; padding: 16px 24px; text-align: center; border-top: 1px solid #e7e5e4;">
            <p style="margin: 0; font-size: 11px; color: #a8a29e;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
