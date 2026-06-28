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
     * Registration always creates a "customer"; there is no automatic
     * first-user elevation. To create your admin account, register through the
     * normal sign-up flow, then explicitly promote that user:
     *   php artisan app:make-admin you@example.com
     */
    public function run(): void
    {
        // Intentionally empty — see docblock above.
    }
}
