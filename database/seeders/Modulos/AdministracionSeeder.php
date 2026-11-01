<?php

namespace Database\Seeders\Modulos;

use App\Models\Cuenta;
use App\Models\Empleado;
use App\Models\Estudiante;
use App\Models\Persona;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
use App\Support\MenuResourceSync;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministracionSeeder extends Seeder
{
    public function run(): void
    {
        MenuResourceSync::sync();
        $this->seedDemoAccounts();
    }

    private function seedDemoAccounts(): void
    {
        // Credenciales solo para pruebas; reemplazarlas antes de usar datos reales.
        $accounts = [
            [
                'role' => 'Administrador',
                'username' => 'admin_prueba',
                'password' => 'Admin2026!',
                'ci' => '99000001',
                'name' => 'Alex',
                'surname' => 'Administrador',
                'second_surname' => 'Prueba',
                'type' => 'admin',
                'email' => 'admin_prueba@example.test',
                'cellphone' => 70000001,
                'phone' => 4450001,
                'address' => 'Av. Institucional 100',
                'birthdate' => '1985-01-15',
                'birthplace' => 'Sacaba',
                'expedition' => 'CB',
                'nationality' => 'Boliviana',
                'sex' => 'MASCULINO',
                'employee' => true,
                'student' => false,
                'job_code' => 'EMP-PRUEBA-001',
                'profession' => 'Adm.',
            ],
            [
                'role' => 'Secretaria',
                'username' => 'secretaria_prueba',
                'password' => 'Secretaria2026!',
                'ci' => '99000002',
                'name' => 'Sara',
                'surname' => 'Secretaria',
                'second_surname' => 'Prueba',
                'type' => 'empleado',
                'email' => 'secretaria_prueba@example.test',
                'cellphone' => 70000002,
                'phone' => 4450002,
                'address' => 'Calle Administrativa 200',
                'birthdate' => '1990-02-20',
                'birthplace' => 'Cochabamba',
                'expedition' => 'CB',
                'nationality' => 'Boliviana',
                'sex' => 'FEMENINO',
                'employee' => true,
                'student' => false,
                'job_code' => 'EMP-PRUEBA-002',
                'profession' => 'Tec.',
            ],
            [
                'role' => 'Docente',
                'username' => 'docente_prueba',
                'password' => 'Docente2026!',
                'ci' => '99000003',
                'name' => 'Daniel',
                'surname' => 'Docente',
                'second_surname' => 'Prueba',
                'type' => 'empleado',
                'email' => 'docente_prueba@example.test',
                'cellphone' => 70000003,
                'phone' => 4450003,
                'address' => 'Av. Académica 300',
                'birthdate' => '1988-03-25',
                'birthplace' => 'Quillacollo',
                'expedition' => 'CB',
                'nationality' => 'Boliviana',
                'sex' => 'MASCULINO',
                'employee' => true,
                'student' => false,
                'job_code' => 'EMP-PRUEBA-003',
                'profession' => 'Lic.',
            ],
            [
                'role' => 'Estudiante',
                'username' => 'estudiante_prueba',
                'password' => 'Estudiante2026!',
                'ci' => '99000004',
                'name' => 'Esteban',
                'surname' => 'Estudiante',
                'second_surname' => 'Prueba',
                'type' => 'estudiante',
                'email' => 'estudiante_prueba@example.test',
                'cellphone' => 70000004,
                'phone' => 4450004,
                'address' => 'Zona Universitaria 400',
                'birthdate' => '2005-04-10',
                'birthplace' => 'Sacaba',
                'expedition' => 'CB',
                'nationality' => 'Boliviana',
                'sex' => 'MASCULINO',
                'employee' => false,
                'student' => true,
                'job_code' => null,
                'profession' => null,
            ],
        ];

        foreach ($accounts as $account) {
            $role = Rol::firstOrCreate(['nombre' => $account['role']]);
            $persona = Persona::updateOrCreate(
                ['ci' => $account['ci']],
                [
                    'tipo' => $account['type'],
                    'celular' => $account['cellphone'],
                    'direccion' => $account['address'],
                    'email' => $account['email'],
                    'fechanacimiento' => $account['birthdate'],
                    'lugarexpedicion' => $account['expedition'],
                    'lugarnacimiento' => $account['birthplace'],
                    'nacionalidad' => $account['nationality'],
                    'nombre' => $account['name'],
                    'primerapellido' => $account['surname'],
                    'segundoapellido' => $account['second_surname'],
                    'sexo' => $account['sex'],
                    'telefono' => $account['phone'],
                ]
            );

            if ($account['employee']) {
                Empleado::updateOrCreate(
                    ['id_persona' => $persona->id_persona],
                    [
                        'abreviaturaprofesion' => $account['profession'],
                        'codigo' => $account['job_code'],
                    ]
                );
            }

            if ($account['student']) {
                Estudiante::firstOrCreate(['id_persona' => $persona->id_persona]);
            }

            $password = Hash::make($account['password']);
            $user = User::updateOrCreate(
                ['id_persona' => $persona->id_persona],
                [
                    'usuario' => $account['username'],
                    'password' => $password,
                ]
            );

            Cuenta::updateOrCreate(
                [
                    'id_persona' => $persona->id_persona,
                    'id_rol' => $role->id_rol,
                ],
                [
                    'usuario' => $account['username'],
                    'password' => $password,
                ]
            );

            if ($account['role'] === 'Administrador') {
                $role->recursos()->sync(Recurso::query()->pluck('id_recurso')->all());
            }
        }
    }
}
