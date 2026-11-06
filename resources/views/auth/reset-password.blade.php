<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Set password · SupportTrack</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/readability.css') }}">
    <link rel="stylesheet" href="{{ asset('css/buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/feature-workflows.css') }}">
</head>
<body class="login-body">
<main class="login-stage">
    <section class="access-card">
                <div class="login-heading"><span class="login-eyebrow">SECURE ACCOUNT ACCESS</span><h1>Choose a password</h1><p>Use at least 12 characters. This link can be used once.</p></div>
                @if($errors->any())<div class="login-error" role="alert">{{ $errors->first() }}</div>@endif
                <form class="login-form" method="post" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="login-field"><label for="email">Work email</label><input class="input" id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', $email) }}"></div>
                    <div class="login-field"><label for="password">New password</label><input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="12" required></div>
                    <div class="login-field"><label for="password_confirmation">Confirm password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required></div>
                    <button class="login-submit" type="submit"><span class="submit-label">Save password</span><span class="submit-arrow" aria-hidden="true">→</span></button>
                </form>
                <p><a href="{{ route('login') }}">Return to sign in</a></p>
    </section>
</main>
</body>
</html>
