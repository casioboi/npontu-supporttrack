<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot password · SupportTrack</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/readability.css') }}">
    <link rel="stylesheet" href="{{ asset('css/buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/feature-workflows.css') }}">
</head>
<body class="login-body">
<main class="login-stage">
    <section class="access-card">
                <div class="login-heading"><span class="login-eyebrow">ACCOUNT RECOVERY</span><h1>Reset your password</h1><p>Enter your work email and, if an account matches, we’ll send a secure reset link.</p></div>
                @if(session('status'))<div class="login-trust" role="status">{{ session('status') }}</div>@endif
                @if($errors->any())<div class="login-error" role="alert">{{ $errors->first() }}</div>@endif
                <form class="login-form" method="post" action="{{ route('password.email') }}">
                    @csrf
                    <div class="login-field"><label for="email">Work email</label><div class="login-input-wrap"><input class="input" id="email" name="email" type="email" autocomplete="email" required autofocus value="{{ old('email') }}"></div></div>
                    <button class="login-submit" type="submit"><span class="submit-label">Send reset link</span><span class="submit-arrow" aria-hidden="true">→</span></button>
                </form>
                <p><a href="{{ route('login') }}">Return to sign in</a></p>
    </section>
</main>
</body>
</html>
