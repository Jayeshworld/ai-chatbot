<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — AI Gateway</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #0f1117; --surface: #1a1d27; --border: #2d3048;
    --text: #e2e8f0; --muted: #8892a4;
    --accent: #6366f1; --accent-hover: #818cf8;
    --success: #10b981; --error: #f87171;
  }
  body {
    background: var(--bg); color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 20px;
  }
  .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
  .logo-icon {
    width: 38px; height: 38px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 18px;
  }
  .logo-text { font-size: 19px; font-weight: 700; }

  .card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 16px; padding: 36px; width: 100%; max-width: 420px;
  }

  /* Progress steps */
  .steps { display: flex; align-items: center; margin-bottom: 28px; }
  .step {
    display: flex; align-items: center; gap: 7px;
    font-size: 12px; font-weight: 600; color: var(--muted);
  }
  .step.active { color: var(--accent); }
  .step.done { color: var(--success); }
  .step-num {
    width: 22px; height: 22px; border-radius: 50%; border: 2px solid currentColor;
    display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;
    flex-shrink: 0;
  }
  .step-line { flex: 1; height: 1px; background: var(--border); margin: 0 8px; }

  h2 { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
  .subtitle { color: var(--muted); font-size: 13px; margin-bottom: 24px; line-height: 1.5; }

  .field { margin-bottom: 16px; }
  label { display: block; font-size: 12px; font-weight: 500; color: var(--muted); margin-bottom: 5px; }
  input {
    width: 100%; background: var(--bg); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text); font-size: 14px;
    padding: 10px 14px; outline: none; transition: border-color .2s; font-family: inherit;
  }
  input:focus { border-color: var(--accent); }
  .field-error { color: var(--error); font-size: 11px; margin-top: 4px; display: none; }

  /* OTP input */
  .otp-wrap { display: flex; gap: 8px; }
  .otp-wrap input {
    flex: 1; text-align: center; font-size: 22px; font-weight: 700;
    padding: 12px 4px; letter-spacing: 2px; font-family: monospace;
  }

  .alert {
    padding: 11px 14px; border-radius: 8px; font-size: 13px;
    margin-bottom: 16px; display: none; line-height: 1.4;
  }
  .alert-error { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3); color: var(--error); }
  .alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: var(--success); }

  .btn {
    width: 100%; border: none; border-radius: 8px; padding: 11px;
    font-size: 14px; font-weight: 600; cursor: pointer; transition: all .2s;
  }
  .btn-primary { background: var(--accent); color: white; }
  .btn-primary:hover { background: var(--accent-hover); }
  .btn-primary:disabled { opacity: .55; cursor: not-allowed; }
  .btn-ghost {
    background: none; border: 1px solid var(--border); color: var(--muted);
    margin-top: 8px; font-size: 13px;
  }
  .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }

  .footer-link { text-align: center; margin-top: 18px; font-size: 13px; color: var(--muted); }
  .footer-link a { color: var(--accent); text-decoration: none; }
  .footer-link a:hover { text-decoration: underline; }

  /* Resend timer */
  .resend-row { text-align: center; margin-top: 14px; font-size: 13px; color: var(--muted); }
  .resend-row a { color: var(--accent); cursor: pointer; }
  .resend-row a:hover { text-decoration: underline; }

  /* Success screen */
  #successScreen { display: none; text-align: center; }
  .success-icon {
    width: 64px; height: 64px; background: rgba(16,185,129,.12);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 28px; margin: 0 auto 20px;
  }
  #successScreen h2 { margin-bottom: 10px; }
  #successScreen p { color: var(--muted); font-size: 14px; line-height: 1.6; }
  .email-highlight { color: var(--text); font-weight: 600; }
</style>
</head>
<body>

<div class="logo">
  <div class="logo-icon">⚡</div>
  <span class="logo-text">AI Gateway</span>
</div>

<div class="card">

  <!-- Step 1: Name + Email -->
  <div id="step1Screen">
    <div class="steps">
      <div class="step active" id="s1"><div class="step-num">1</div><span>Your details</span></div>
      <div class="step-line"></div>
      <div class="step" id="s2"><div class="step-num">2</div><span>Verify email</span></div>
    </div>

    <h2>Create your account</h2>
    <p class="subtitle">Enter your details and we'll send a verification code to your email.</p>

    <div class="alert alert-error" id="step1Alert"></div>

    <div class="field">
      <label>Full Name</label>
      <input type="text" id="nameInput" placeholder="Your name" autocomplete="name">
      <div class="field-error" id="nameErr"></div>
    </div>
    <div class="field">
      <label>Email Address</label>
      <input type="email" id="emailInput" placeholder="you@example.com" autocomplete="email">
      <div class="field-error" id="emailErr"></div>
    </div>

    <button class="btn btn-primary" id="sendOtpBtn">Send Verification Code</button>
    <div class="footer-link">Already have an API key? <a href="/">Sign in</a></div>
  </div>

  <!-- Step 2: OTP -->
  <div id="step2Screen" style="display:none">
    <div class="steps">
      <div class="step done" id="s1d"><div class="step-num">✓</div><span>Your details</span></div>
      <div class="step-line" style="background:var(--accent);opacity:.4"></div>
      <div class="step active" id="s2a"><div class="step-num">2</div><span>Verify email</span></div>
    </div>

    <h2>Check your email</h2>
    <p class="subtitle" id="otpSubtitle">We sent a 6-digit code to <strong id="sentToEmail"></strong>. Enter it below.</p>

    <div class="alert alert-error" id="step2Alert"></div>

    <div class="field">
      <label>Verification Code</label>
      <input type="text" id="otpInput" placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code" style="text-align:center;font-size:26px;font-weight:700;letter-spacing:8px;font-family:monospace;">
      <div class="field-error" id="otpErr"></div>
    </div>

    <button class="btn btn-primary" id="verifyOtpBtn">Verify & Create Account</button>
    <button class="btn btn-ghost" onclick="goBack()">← Use a different email</button>

    <div class="resend-row">
      <span id="resendTimerText">Resend code in <strong id="resendTimer">60</strong>s</span>
      <span id="resendLink" style="display:none"><a onclick="resendOtp()">Resend code</a></span>
    </div>
  </div>

  <!-- Success -->
  <div id="successScreen">
    <div class="success-icon">✉️</div>
    <h2>You're all set!</h2>
    <p>Your API key has been sent to<br><span class="email-highlight" id="successEmail"></span></p>
    <p style="margin-top:12px;font-size:13px;color:var(--muted)">Check your inbox, copy your key, and paste it into the chat login screen.</p>
    <a href="/" style="display:inline-block;margin-top:24px;background:var(--accent);color:white;text-decoration:none;padding:11px 28px;border-radius:8px;font-size:14px;font-weight:600;">Open Chat →</a>
  </div>

</div>

<script>
let userEmail = '';
let userName = '';
let resendInterval = null;

// ── Step 1: Send OTP ─────────────────────────────────────────────────────────

document.getElementById('sendOtpBtn').addEventListener('click', sendOtp);
['nameInput','emailInput'].forEach(id =>
  document.getElementById(id).addEventListener('keydown', e => { if (e.key === 'Enter') sendOtp(); })
);

async function sendOtp() {
  clearErrors();
  const name  = document.getElementById('nameInput').value.trim();
  const email = document.getElementById('emailInput').value.trim();
  let ok = true;

  if (!name) { showFieldErr('nameErr', 'Name is required.'); ok = false; }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showFieldErr('emailErr', 'A valid email is required.'); ok = false;
  }
  if (!ok) return;

  const btn = document.getElementById('sendOtpBtn');
  btn.disabled = true; btn.textContent = 'Sending…';

  try {
    const res = await fetch('/api/v1/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ name, email }),
    });
    const data = await res.json();

    if (res.ok) {
      userEmail = email; userName = name;
      document.getElementById('sentToEmail').textContent = email;
      document.getElementById('step1Screen').style.display = 'none';
      document.getElementById('step2Screen').style.display = 'block';
      document.getElementById('otpInput').focus();
      startResendTimer();
    } else {
      const msg = data.error?.message || data.errors?.email?.[0] || data.errors?.name?.[0] || 'Something went wrong.';
      if (data.errors?.email) showFieldErr('emailErr', data.errors.email[0]);
      else if (data.errors?.name) showFieldErr('nameErr', data.errors.name[0]);
      else showAlert('step1Alert', msg);
    }
  } catch {
    showAlert('step1Alert', 'Network error. Please try again.');
  } finally {
    btn.disabled = false; btn.textContent = 'Send Verification Code';
  }
}

// ── Step 2: Verify OTP ───────────────────────────────────────────────────────

document.getElementById('verifyOtpBtn').addEventListener('click', verifyOtp);
document.getElementById('otpInput').addEventListener('keydown', e => { if (e.key === 'Enter') verifyOtp(); });

// Auto-submit when 6 digits entered
document.getElementById('otpInput').addEventListener('input', function() {
  this.value = this.value.replace(/\D/g, '').slice(0, 6);
  if (this.value.length === 6) verifyOtp();
});

async function verifyOtp() {
  const otp = document.getElementById('otpInput').value.trim();
  document.getElementById('step2Alert').style.display = 'none';
  if (otp.length !== 6) { showAlert('step2Alert', 'Please enter the full 6-digit code.'); return; }

  const btn = document.getElementById('verifyOtpBtn');
  btn.disabled = true; btn.textContent = 'Verifying…';

  try {
    const res = await fetch('/api/v1/auth/verify-otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: userEmail, otp }),
    });
    const data = await res.json();

    if (res.ok) {
      clearInterval(resendInterval);
      document.getElementById('step2Screen').style.display = 'none';
      document.getElementById('successEmail').textContent = userEmail;
      document.getElementById('successScreen').style.display = 'block';
    } else {
      const msg = data.error?.message || 'Invalid code.';
      showAlert('step2Alert', msg);
      document.getElementById('otpInput').value = '';
      document.getElementById('otpInput').focus();
    }
  } catch {
    showAlert('step2Alert', 'Network error. Please try again.');
  } finally {
    btn.disabled = false; btn.textContent = 'Verify & Create Account';
  }
}

function goBack() {
  clearInterval(resendInterval);
  document.getElementById('step2Screen').style.display = 'none';
  document.getElementById('step1Screen').style.display = 'block';
  document.getElementById('otpInput').value = '';
}

// ── Resend timer ─────────────────────────────────────────────────────────────

function startResendTimer(seconds = 60) {
  clearInterval(resendInterval);
  document.getElementById('resendTimerText').style.display = 'inline';
  document.getElementById('resendLink').style.display = 'none';
  document.getElementById('resendTimer').textContent = seconds;

  resendInterval = setInterval(() => {
    seconds--;
    document.getElementById('resendTimer').textContent = seconds;
    if (seconds <= 0) {
      clearInterval(resendInterval);
      document.getElementById('resendTimerText').style.display = 'none';
      document.getElementById('resendLink').style.display = 'inline';
    }
  }, 1000);
}

async function resendOtp() {
  document.getElementById('step2Alert').style.display = 'none';
  try {
    const res = await fetch('/api/v1/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ name: userName, email: userEmail }),
    });
    if (res.ok) {
      startResendTimer();
      document.getElementById('otpInput').value = '';
      document.getElementById('otpInput').focus();
    } else {
      const data = await res.json();
      showAlert('step2Alert', data.error?.message || 'Failed to resend code.');
    }
  } catch {
    showAlert('step2Alert', 'Network error. Please try again.');
  }
}

// ── Utils ────────────────────────────────────────────────────────────────────

function showFieldErr(id, msg) {
  const el = document.getElementById(id);
  el.textContent = msg; el.style.display = 'block';
}
function showAlert(id, msg) {
  const el = document.getElementById(id);
  el.textContent = msg; el.style.display = 'block';
}
function clearErrors() {
  ['nameErr','emailErr'].forEach(id => { const el = document.getElementById(id); el.textContent=''; el.style.display='none'; });
  document.getElementById('step1Alert').style.display = 'none';
}
</script>
</body>
</html>
