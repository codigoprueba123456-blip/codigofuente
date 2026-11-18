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
use RuntimeException;

class AdministracionSeeder extends Seeder
{
    // Los roles que no son de acceso completo usan una lista explícita de URL del menú.
    // Mantener estas rutas aquí evita asignarles permisos nuevos solo porque se agregue una opción al menú.
    private const ROLE_RESOURCE_URLS = [
        'Docente' => [
            'planes/materias',
        ],
        'Secretaria' => [
            'admisiones/postulantes',
            'inscripciones/estudiante_nuevo',
            'inscripciones/estudiante_nuevo_postulacion',
            'inscripciones/estudiante_regular',
            'inscripciones/cambio_carrera',
            'inscripciones/inscripcion_manual',
            'inscripciones/inscriptos',
            'pagos/pago',
        ],
        'Estudiante' => [
            'estudiantes/estado_inscripcion',
            'estudiantes/historial_economico',
            'estudiantes/file_estudiante',
        ],
        'Director Administrativo' => [
            'admin/instituto',
            'admin/empleados',
            'admin/usuarios',
            'pagos/pago',
            'reportes/malla-curricular',
            'reportes/dashboard',
            'reportes/inscripciones/lista-postulantes',
            'reportes/inscripciones/lista-inscritos-carrera',
            'reportes/inscripciones/lista-inscritos-paralelo',
            'reportes/inscripciones/lista-inscritos-grupo',
            'reportes/inscripciones/libro-inscripciones',
            'reportes/inscripciones/libro-inscripciones-carrera',
            'reportes/inscripciones/lista-inscritos-multicarrera',
        ],
    ];

    private const FULL_ACCESS_ROLES = [
        'Administrador',
        'Rector',
        'Director Académico',
    ];

    private const ADDITIONAL_ROLES = [
        'Rector',
        'Director Académico',
        'Director Administrativo',
    ];

    /**
     * Sincroniza recursos del menú y luego asegura las cuentas de prueba y sus permisos.
     */
    public function run(): void
    {
        $menuItems = $this->menuItems();
        MenuResourceSync::sync($menuItems);
        $this->seedDemoAccounts($menuItems);
    }

    /**
     * Crea o actualiza las cuentas de prueba y sincroniza los permisos definidos para cada rol.
     */
    private function seedDemoAccounts(array $menuItems): void
    {
        // Credenciales solo para pruebas; reemplazarlas antes de usar datos reales.
        $accounts = [
            [
                'role' => 'Administrador',
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
            $username = $account['employee']
                ? $this->employeeUsername($persona)
                : $this->studentUsername($persona);
            $user = User::updateOrCreate(
                ['id_persona' => $persona->id_persona],
                [
                    'usuario' => $username,
                    'password' => $password,
                ]
            );

            Cuenta::updateOrCreate(
                [
                    'id_persona' => $persona->id_persona,
                    'id_rol' => $role->id_rol,
                ],
                [
                    'usuario' => $username,
                    'password' => $password,
                ]
            );

            $this->syncRoleResources($role, $menuItems);
        }

        foreach (self::ADDITIONAL_ROLES as $roleName) {
            $role = Rol::firstOrCreate(['nombre' => $roleName]);
            $this->syncRoleResources($role, $menuItems);
        }
    }

    /**
     * Devuelve el menú de funcionalidades y usa el menú AdminLTE si no hay uno personalizado.
     */
    private function menuItems(): array
    {
        $menu = config('functionality.menu', []);

        return $menu ?: config('adminlte.menu', []);
    }

    /**
     * Reemplaza los permisos del rol por los recursos requeridos y falla si falta una URL configurada.
     */
    private function syncRoleResources(Rol $role, array $menuItems): void
    {
        $menuUrls = $this->menuResourceUrls($menuItems);
        // Administrador, Rector y Director Académico reciben el menú completo; para los demás,
        // una URL no declarada significa que el recurso no se asigna.
        $resourceUrls = in_array($role->nombre, self::FULL_ACCESS_ROLES, true)
            ? $menuUrls
            : (self::ROLE_RESOURCE_URLS[$role->nombre] ?? []);
        $missingUrls = array_diff($resourceUrls, $menuUrls);

        if ($missingUrls) {
            throw new RuntimeException(
                'No se encontraron estos recursos en el menú: '.implode(', ', $missingUrls)
            );
        }

        $resources = Recurso::query()
            ->whereIn('urlpattern', $resourceUrls)
            ->get(['id_recurso', 'urlpattern'])
            ->keyBy('urlpattern');
        $missingResources = array_diff($resourceUrls, $resources->keys()->all());

        if ($missingResources) {
            throw new RuntimeException(
                'No se pudieron sincronizar estos recursos: '.implode(', ', $missingResources)
            );
        }

        $role->recursos()->sync($resources->pluck('id_recurso')->all());
    }

    /**
     * Extrae URLs navegables del menú de forma recursiva y elimina duplicados.
     */
    private function menuResourceUrls(array $items): array
    {
        $urls = [];
        foreach ($items as $item) {
            // Solo las entradas navegables son permisos; encabezados y elementos decorativos no lo son.
            if (! empty($item['url']) && $item['url'] !== '#' && ! isset($item['type'])) {
                $urls[] = trim($item['url'], '/');
            }

            // También incluye los enlaces anidados dentro de los submenús.
            if (! empty($item['submenu']) && is_array($item['submenu'])) {
                $urls = [...$urls, ...$this->menuResourceUrls($item['submenu'])];
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Conserva el username numérico existente o genera el siguiente correlativo del año actual.
     */
    private function employeeUsername(Persona $persona): string
    {
        $year = date('Y');
        $existingUsername = User::where('id_persona', $persona->id_persona)->value('usuario');

        if (is_string($existingUsername) && preg_match('/^' . preg_quote($year, '/') . '\d{4}$/', $existingUsername)) {
            return $existingUsername;
        }

        $lastUsername = User::where('usuario', 'LIKE', $year . '%')
            ->orderByDesc('usuario')
            ->value('usuario');

        $counter = $lastUsername ? ((int) substr($lastUsername, 4)) + 1 : 1;

        return $year . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Conserva un username numérico existente; si no existe, busca un número libre en users y cuenta.
     */
    private function studentUsername(Persona $persona): string
    {
        $existingUsername = User::where('id_persona', $persona->id_persona)->value('usuario');

        if (is_string($existingUsername) && preg_match('/^20\d{6,}$/', $existingUsername)) {
            return $existingUsername;
        }

        $year = now()->format('Y');
        $pattern = '/^'.preg_quote($year, '/').'(\d{4,})$/';
        $usernames = User::where('usuario', 'like', $year.'%')
            ->pluck('usuario')
            ->merge(Cuenta::where('usuario', 'like', $year.'%')->pluck('usuario'));
        $lastNumber = $usernames->reduce(
            static function (int $current, string $username) use ($pattern): int {
                return preg_match($pattern, $username, $matches)
                    ? max($current, (int) $matches[1])
                    : $current;
            },
            0
        );

        do {
            $username = $year.str_pad((string) ++$lastNumber, 4, '0', STR_PAD_LEFT);
        } while (User::where('usuario', $username)->exists()
            || Cuenta::where('usuario', $username)->exists());

        return $username;
    }
}
