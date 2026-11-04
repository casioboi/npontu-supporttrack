<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('supporttrack:admin', function () {
    $name = $this->ask('Administrator full name');
    $email = strtolower(trim((string) $this->ask('Administrator email')));
    $password = $this->secret('Administrator password (minimum 12 characters)');
    $confirmation = $this->secret('Confirm password');

    $validator = Validator::make(compact('name', 'email', 'password', 'confirmation'), [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:12', 'same:confirmation'],
    ]);

    if ($validator->fails()) {
        $this->error($validator->errors()->first());
        return self::FAILURE;
    }

    $user = User::updateOrCreate(['email' => $email], [
        'name' => $name,
        'password' => Hash::make($password),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->info("Administrator account ready for {$user->email}.");
    return self::SUCCESS;
})->purpose('Create or update an administrator using hidden password prompts');
