<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@polysense.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Usuario Test',
            'email' => 'user@polysense.com',
            'password' => bcrypt('user123'),
            'role' => 'user',
        ]);

        $this->call([
            NotificationSeeder::class,
            ReportSeeder::class,
        ]);
    }
}
