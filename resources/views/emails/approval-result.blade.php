<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $result['success'] ? 'Success' : 'Error' }} — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f4; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); max-width: 420px; width: 100%; padding: 48px 32px; text-align: center; }
        .icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .icon-success { background: #dcfce7; }
        .icon-error { background: #fee2e2; }
        .icon svg { width: 32px; height: 32px; }
        .icon-success svg { color: #16a34a; }
        .icon-error svg { color: #dc2626; }
        h1 { font-size: 20px; font-weight: 700; color: #1c1917; margin-bottom: 8px; }
        p { font-size: 14px; color: #57534e; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; background: #7f1d1d; color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 10px; transition: background 0.2s; }
        .btn:hover { background: #991b1b; }
    </style>
</head>
<body>
    <div class="card">
        @if($result['success'])
        <div class="icon icon-success">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1>{{ $action === 'approve' ? 'Invoice Approved' : 'Invoice Rejected' }}</h1>
        @else
        <div class="icon icon-error">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1>Action Failed</h1>
        @endif
        <p>{{ $result['message'] }}</p>
        <a href="{{ url('/sales/invoices') }}" class="btn">Go to Invoices</a>
    </div>
</body>
</html>
