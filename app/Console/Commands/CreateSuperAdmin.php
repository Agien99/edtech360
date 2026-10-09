<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    protected $signature = 'edtech:create-super-admin';

    protected $description = 'Create the initial EdTech360 Super Admin account';

    public function handle(): int
    {
        if (! Role::where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->exists()) {
            $this->error(
                'Super Admin role is missing. Run the roles seeder first.'
            );

            return self::FAILURE;
        }

        if (User::role('super_admin')->exists()) {
            $this->error(
                'A Super Admin already exists. This command is for initial setup only.'
            );

            return self::FAILURE;
        }

        $this->info('EdTech360 - Initial Super Admin Setup');

        $name = trim((string) $this->ask('Full name'));

        if ($name === '' || mb_strlen($name) > 255) {
            $this->error('Name must be between 1 and 255 characters.');

            return self::FAILURE;
        }

        $email = mb_strtolower(
            trim((string) $this->ask('Email address'))
        );

        $emailValidator = Validator::make(
            ['email' => $email],
            [
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],
            ]
        );

        if ($emailValidator->fails()) {
            $this->error(
                'Invalid email address or an account with this email already exists.'
            );

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password');

        if (mb_strlen($password) < 12) {
            $this->error(
                'Password must contain at least 12 characters.'
            );

            return self::FAILURE;
        }

        $confirmation = (string) $this->secret(
            'Confirm password'
        );

        if (! hash_equals($password, $confirmation)) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("Name: {$name}");
        $this->line("Email: {$email}");

        if (! $this->confirm('Create this Super Admin account?', false)) {
            $this->warn('Account creation cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($name, $email, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_active' => true,
            ]);

            $user->assignRole('super_admin');
        });

        $this->info('Super Admin account created successfully.');

        return self::SUCCESS;
    }
}