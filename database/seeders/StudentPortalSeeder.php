<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentPortalSeeder extends Seeder
{
    public function run(): void
    {
        $role = Rol::firstOrCreate(['nombre' => 'Estudiante']);
        $persona = Persona::updateOrCreate(
            ['ci' => '87654322'],
            [
                'tipo' => 'estudiante',
                'nombre' => 'Daynor',
                'primerapellido' => 'Estudiante',
                'segundoapellido' => 'Demo',
                'email' => 'daynor.estudiante@example.com',
                'nacionalidad' => 'Boliviana',
            ]
        );

        Estudiante::firstOrCreate(['id_persona' => $persona->id_persona]);

        $password = Hash::make('estudiante123');
        $user = User::updateOrCreate(
            ['id_persona' => $persona->id_persona],
            [
                'usuario' => '20260003',
                'password' => $password,
            ]
        );

        DB::table('cuenta')->updateOrInsert(
            [
                'id_persona' => $persona->id_persona,
                'id_rol' => $role->id_rol,
            ],
            [
                'usuario' => $user->usuario,
                'password' => $password,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
