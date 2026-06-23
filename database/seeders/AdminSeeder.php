<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * NOTE: Do NOT seed admin credentials here.
     *
     * The FIRST user to register on the site is automatically elevated to the
     * "admin" role (see AGENTS.md). To create your admin account, simply
     * register through the normal sign-up flow. No seed data is needed.
     *
     * If you need to manually promote an existing user to admin, run:
     *   php artisan tinker
     *   >>> \App\Models\User::find(1)->update(['role' => 'admin']);
     */
    public function run(): void
    {
        // Intentionally empty — see docblock above.
    }
}
