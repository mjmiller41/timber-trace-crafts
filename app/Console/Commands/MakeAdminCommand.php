<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'app:make-admin {email : Email of an existing user to promote}';

    protected $description = 'Promote an existing user to the admin role';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        if ($user->role === 'admin') {
            $this->info("{$email} is already an admin.");

            return self::SUCCESS;
        }

        $user->update(['role' => 'admin']);

        $this->info("{$email} is now an admin.");

        return self::SUCCESS;
    }
}
