<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome to AI Gateway</title>
</head>
<body style="margin:0;padding:0;background:#0f1117;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f1117;padding:40px 20px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

      <!-- Logo -->
      <tr><td style="padding-bottom:32px;text-align:center;">
        <div style="display:inline-flex;align-items:center;gap:10px;">
          <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;vertical-align:middle;">⚡</div>
          <span style="font-size:20px;font-weight:700;color:#e2e8f0;vertical-align:middle;">AI Gateway</span>
        </div>
      </td></tr>

      <!-- Card -->
      <tr><td style="background:#1a1d27;border:1px solid #2d3048;border-radius:16px;padding:40px;">

        <p style="margin:0 0 8px;font-size:24px;font-weight:700;color:#e2e8f0;">Welcome, {{ $name }}! 🎉</p>
        <p style="margin:0 0 28px;font-size:14px;color:#8892a4;line-height:1.6;">
          Your email has been verified. Here is your API key to access the AI Gateway chat.
          Keep it safe — treat it like a password.
        </p>

        <!-- API Key box -->
        <div style="background:#0f1117;border:1px solid #2d3048;border-radius:12px;padding:20px;margin-bottom:28px;">
          <p style="margin:0 0 8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#8892a4;">Your API Key</p>
          <p style="margin:0;font-size:13px;font-weight:700;color:#10b981;font-family:monospace;word-break:break-all;line-height:1.8;">{{ $apiKey }}</p>
        </div>

        <!-- Steps -->
        <p style="margin:0 0 16px;font-size:14px;font-weight:600;color:#e2e8f0;">How to get started:</p>
        <table width="100%" cellpadding="0" cellspacing="0">
          @foreach([
            ['1', 'Open the chat', 'Go to ' . $chatUrl],
            ['2', 'Enter your API key', 'Paste the key above into the login screen'],
            ['3', 'Start chatting', 'Ask anything — the AI is ready to help'],
          ] as [$num, $title, $desc])
          <tr><td style="padding-bottom:12px;">
            <table cellpadding="0" cellspacing="0"><tr>
              <td style="width:28px;vertical-align:top;padding-top:2px;">
                <div style="width:22px;height:22px;background:rgba(99,102,241,.2);border-radius:50%;text-align:center;line-height:22px;font-size:11px;font-weight:700;color:#6366f1;">{{ $num }}</div>
              </td>
              <td style="padding-left:10px;">
                <p style="margin:0;font-size:13px;font-weight:600;color:#e2e8f0;">{{ $title }}</p>
                <p style="margin:2px 0 0;font-size:12px;color:#8892a4;">{{ $desc }}</p>
              </td>
            </tr></table>
          </td></tr>
          @endforeach
        </table>

        <!-- CTA button -->
        <div style="text-align:center;margin-top:28px;">
          <a href="{{ $chatUrl }}" style="display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600;">
            Open AI Gateway →
          </a>
        </div>

        <hr style="border:none;border-top:1px solid #2d3048;margin:28px 0;">
        <p style="margin:0;font-size:12px;color:#4a5568;line-height:1.6;">
          Your API key gives full access to your account. Never share it publicly.
          If you suspect it has been compromised, contact your administrator.
        </p>

      </td></tr>

      <!-- Footer -->
      <tr><td style="padding-top:24px;text-align:center;">
        <p style="margin:0;font-size:12px;color:#4a5568;">
          © {{ date('Y') }} AI Gateway. This is an automated message, please do not reply.
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
