<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#f4f5f7">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Sign in · SupportTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"><link rel="stylesheet" href="{{ asset('css/readability.css') }}">
<link rel="stylesheet" href="{{ asset('css/buttons.css') }}"></head>
<body class="login-body">
    <main class="login-stage">
        <div class="login-card">
            <section class="login-promo" aria-label="About SupportTrack">
                <div class="promo-orb promo-orb-one"></div>
                <div class="promo-orb promo-orb-two"></div>
                <a class="login-brand" href="{{ route('login') }}" aria-label="SupportTrack home">
                    <span class="login-brand-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 18V6h3.2l4.8 6.2L16.8 6H20v12h-3V10.7l-5 6.1-5-6.1V18H4Z" fill="currentColor"/></svg></span>
                    <span>SupportTrack</span>
                </a>
                <div class="promo-copy">
                    <span class="promo-kicker"><span class="pulse-dot"></span> APPLICATIONS SUPPORT OPERATIONS</span>
                    <h1>Keep every shift<br>moving <span>forward.</span></h1>
                    <p>One clear view of the work, the findings, and what comes next.</p>
                </div>
                <div class="promo-visual" aria-hidden="true">
                    <div class="visual-window">
                        <div class="visual-window-top"><span></span><span></span><span></span><i>SHIFT OVERVIEW</i></div>
                        <div class="visual-row"><div class="visual-icon blue">↗</div><div class="visual-lines"><b></b><i></i></div><span class="visual-pill green">DONE</span></div>
                        <div class="visual-row"><div class="visual-icon amber">◷</div><div class="visual-lines"><b></b><i></i></div><span class="visual-pill gold">PENDING</span></div>
                        <div class="visual-row"><div class="visual-icon pale">✓</div><div class="visual-lines"><b></b><i></i></div><span class="visual-pill green">DONE</span></div>
                        <div class="visual-handover"><span class="handover-check">✓</span><span><b>Handover ready</b><i>All updates captured</i></span><span class="handover-arrow">↗</span></div>
                    </div>
                    <div class="floating-note"><span class="floating-avatar">A</span><span><b>Update recorded</b><i>Just now · Status changed</i></span><span class="floating-check">✓</span></div>
                    <div class="visual-spark spark-one">✳</div><div class="visual-spark spark-two">✦</div>
                </div>
                <div class="promo-foot"><span>NPONTU TECHNOLOGIES</span><span>CLARITY IN EVERY HANDOVER</span></div>
            </section>

            <section class="login-panel">
                <div class="login-panel-inner">
                    <div class="login-mobile-brand"><span class="login-brand-mark"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 18V6h3.2l4.8 6.2L16.8 6H20v12h-3V10.7l-5 6.1-5-6.1V18H4Z" fill="currentColor"/></svg></span><span>SupportTrack</span></div>
                    <div class="login-heading"><span class="login-eyebrow">YOUR TEAM, IN SYNC</span><h2>Welcome back</h2><p>Sign in to your SupportTrack workspace.</p></div>
                    @if($errors->any())
                        <div class="login-error" role="alert"><span class="error-symbol">!</span><span>{{ $errors->first() }}</span></div>
                    @endif
                    @if(session('status'))<div class="login-trust" role="status">{{ session('status') }}</div>@endif
                    <form class="login-form" method="post" action="{{ route('login.submit') }}">
                        @csrf
                        <div class="login-field">
                            <label for="email">Work email</label>
                            <div class="login-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.75A1.75 1.75 0 0 1 5.75 5h12.5A1.75 1.75 0 0 1 20 6.75v10.5A1.75 1.75 0 0 1 18.25 19H5.75A1.75 1.75 0 0 1 4 17.25V6.75Zm1.8.1 6.2 4.7 6.2-4.7H5.8Zm12.7 1.9-6.16 4.67a.6.6 0 0 1-.72 0L5.45 8.75v8.5c0 .17.13.3.3.3h12.5c.17 0 .3-.13.3-.3v-8.5Z" fill="currentColor"/></svg><input id="email" name="email" type="email" autocomplete="username" required autofocus value="{{ old('email') }}" placeholder="you@npontu.com"></div>
                        </div>
                        <div class="login-field">
                            <div class="password-label"><label for="password">Password</label><span class="secure-label"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3h1.25A1.75 1.75 0 0 1 20 11.75v7.5A1.75 1.75 0 0 1 18.25 21H5.75A1.75 1.75 0 0 1 4 19.25v-7.5A1.75 1.75 0 0 1 5.75 10H7Zm2 0h6V7a3 3 0 0 0-6 0v3Zm3 3a1.5 1.5 0 0 0-.75 2.8V18h1.5v-2.2A1.5 1.5 0 0 0 12 13Z" fill="currentColor"/></svg> SECURE SIGN IN</span></div>
                            <div class="login-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3h1.25A1.75 1.75 0 0 1 20 11.75v7.5A1.75 1.75 0 0 1 18.25 21H5.75A1.75 1.75 0 0 1 4 19.25v-7.5A1.75 1.75 0 0 1 5.75 10H7Zm2 0h6V7a3 3 0 0 0-6 0v3Zm3 3a1.5 1.5 0 0 0-.75 2.8V18h1.5v-2.2A1.5 1.5 0 0 0 12 13Z" fill="currentColor"/></svg><input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password"><button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password" aria-pressed="false"><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.2 0 9.1 4.4 10 7-.9 2.6-4.8 7-10 7S2.9 14.6 2 12c.9-2.6 4.8-7 10-7Zm0 2c-3.7 0-6.8 2.9-7.8 5 1 2.1 4.1 5 7.8 5s6.8-2.9 7.8-5c-1-2.1-4.1-5-7.8-5Zm0 1.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7Zm0 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" fill="currentColor"/></svg><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3.3 2 18.7 18.7-1.4 1.4-3.5-3.5a11 11 0 0 1-5.1 1.4c-5.2 0-9.1-4.4-10-7a13 13 0 0 1 4.1-5.3L1.9 3.4 3.3 2Zm4.3 7.1A10.7 10.7 0 0 0 4.2 13c1 2.1 4.1 5 7.8 5 1.1 0 2.1-.3 3-.7l-2-2a3.5 3.5 0 0 1-4.6-4.6l-.8-.8ZM12 5c5.2 0 9.1 4.4 10 7a12.8 12.8 0 0 1-2.8 4.1l-1.4-1.4a10.5 10.5 0 0 0 2-2.7c-1-2.1-4.1-5-7.8-5-.6 0-1.2.1-1.7.2L8.6 5.5A11 11 0 0 1 12 5Zm0 3.5a3.5 3.5 0 0 1 3.5 3.5c0 .4-.1.8-.2 1.1l-4.4-4.4c.3-.1.7-.2 1.1-.2Z" fill="currentColor"/></svg></button></div>
                        </div>
                        <div class="login-options"><label class="remember-option"><input type="checkbox" name="remember" value="1"><span class="custom-checkbox"></span>Keep me signed in</label><a href="{{ route('password.request') }}">Forgot password?</a></div>
                        <button class="login-submit" type="submit"><span class="submit-label">Sign in to workspace</span><span class="submit-arrow" aria-hidden="true">→</span><span class="submit-spinner" aria-hidden="true"></span></button>
                    </form>
                    <div class="login-divider"><span></span><i>YOUR SUPPORT WORKSPACE</i><span></span></div>
                    <div class="login-trust"><span class="trust-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 5v6c0 5.2 3.4 9.4 8 11 4.6-1.6 8-5.8 8-11V5l-8-3Zm0 3 5 1.9V11c0 3.8-2.2 7-5 8.4-2.8-1.4-5-4.6-5-8.4V6.9L12 5Zm-1 9.6-2-2-1.4 1.4L11 17.4l5.4-5.4L15 10.6l-4 4Z" fill="currentColor"/></svg></span><span><b>Protected team access</b><i>Your sign-in is secured and activity is auditable.</i></span></div>
                    <p class="login-footer">© {{ now()->year }} Npontu Technologies <span>·</span> Applications Support</p>
                </div>
            </section>
        </div>
    </main>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        passwordToggle.addEventListener('click', () => {
            const showing = passwordInput.type === 'password';
            passwordInput.type = showing ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(showing));
            passwordToggle.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
        });
        document.querySelector('.login-form').addEventListener('submit', (event) => {
            if (!event.currentTarget.reportValidity()) return;
            const button = event.currentTarget.querySelector('.login-submit');
            button.classList.add('is-loading');
            button.disabled = true;
        });
    </script>
</body>
</html>
