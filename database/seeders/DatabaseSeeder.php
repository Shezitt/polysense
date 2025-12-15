<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario administrador por defecto
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@polysense.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        // Crear usuario de prueba regular
        User::create([
            'name' => 'Usuario Test',
            'email' => 'user@polysense.com',
            'password' => bcrypt('user123'),
            'role' => 'user',
        ]);

        // Cargar comandos de voz predeterminados
        $this->call([
            VoiceCommandSeeder::class,
        ]);
    }
}
