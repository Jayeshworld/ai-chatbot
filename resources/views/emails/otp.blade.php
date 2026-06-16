<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verification Code</title>
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

        <p style="margin:0 0 8px;font-size:24px;font-weight:700;color:#e2e8f0;">Your verification code</p>
        <p style="margin:0 0 28px;font-size:14px;color:#8892a4;line-height:1.6;">
          Hi {{ $name }}, use the code below to verify your email address and complete your registration.
        </p>

        <!-- OTP box -->
        <div style="background:#0f1117;border:1px solid #2d3048;border-radius:12px;padding:28px;text-align:center;margin-bottom:28px;">
          <p style="margin:0 0 8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#8892a4;">Verification Code</p>
          <p style="margin:0;font-size:42px;font-weight:800;letter-spacing:10px;color:#6366f1;font-family:monospace;">{{ $otp }}</p>
        </div>

        <p style="margin:0 0 8px;font-size:13px;color:#8892a4;line-height:1.6;">
          This code expires in <strong style="color:#e2e8f0;">10 minutes</strong>. If you didn't request this, you can safely ignore this email.
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
