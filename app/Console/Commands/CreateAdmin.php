<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use App\Models\User;
use App\Rules\SafeEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Administrator display name}
        {--email= : Administrator email address}
        {--password= : Password (omit to enter it securely)}
        {--password-confirmation= : Password confirmation for non-interactive automation}';

    protected $description = 'Create the first production administrator account';

    public function handle(): int
    {
        $name = trim((string) $this->option('name'));
        $email = strtolower(trim((string) $this->option('email')));
        $password = (string) ($this->option('password') ?: $this->secret('Password'));
        $confirmation = (string) ($this->option('password-confirmation') ?: '');

        if ($confirmation === '' && $this->input->isInteractive()) {
            $confirmation = (string) $this->secret('Confirm password');
        }

        if ($confirmation === '' && ! $this->input->isInteractive()) {
            $confirmation = $password;
        }

        if ($password !== $confirmation) {
            $this->error('The password confirmation does not match.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:4096'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('A user with that email already exists.');

            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'avatar_color' => '#7c3aed',
        ]);

        SystemLog::create([
            'user_id' => $admin->id,
            'action' => 'Created administrator',
            'description' => 'Administrator account created from the CLI.',
            'ip_address' => 'cli',
        ]);

        $this->info("Administrator {$admin->email} created successfully.");

        return self::SUCCESS;
    }
}

