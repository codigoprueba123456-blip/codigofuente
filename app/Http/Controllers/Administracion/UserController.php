<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Persona;
use App\Models\Rol;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class UserController extends Controller
{
    /**
     * Lista usuarios con persona, cuenta y rol precargados para la tabla de administración.
     * El término search se compara parcialmente con username, nombre, apellidos, email y rol.
     */
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('search', ''));
        $usuarios = User::with('persona', 'cuenta.rol')
            ->when($buscar !== '', fn ($query) => $query->where(function ($query) use ($buscar): void {
                $term = '%'.$buscar.'%';
                $query->where('usuario', 'like', $term)
                    ->orWhereHas('persona', fn ($persona) => $persona
                        ->where('nombre', 'like', $term)
                        ->orWhere('primerapellido', 'like', $term)
                        ->orWhere('segundoapellido', 'like', $term)
                        ->orWhere('email', 'like', $term))
                    ->orWhereHas('cuenta.rol', fn ($rol) => $rol->where('nombre', 'like', $term));
            }))
            ->get();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    /**
     * Mantiene la acción convencional del controlador, aunque el alta independiente no está implementada.
     * La interfaz actual no muestra un formulario de creación de usuarios; las credenciales se generan
     * al crear un empleado o al procesar otros flujos que vinculan una persona.
     */
    public function create()
    {
        
    }

    /**
     * Valida que la persona exista y aún no tenga usuario, genera username correlativo y contraseña.
     * Guarda el hash en users y entrega un PDF con la contraseña inicial para su distribución.
     */
    public function store(Request $request)
    {

        $request->validate([
            'id_persona' => 'required|exists:personas,id_persona|unique:users,id_persona',
        ]);

        $persona = Persona::findOrFail($request->id_persona);


        $anio = date('Y');
        $ultimoUser = User::where('usuario', 'LIKE', $anio . '%')
                             ->orderBy('usuario', 'desc')
                             ->first();

        $correlativo = $ultimoUser ? ((int) substr($ultimoUser->usuario, 4)) + 1 : 1;
        $username = $anio . str_pad($correlativo, 4, '0', STR_PAD_LEFT);

        $plainPassword = Str::password(8, letters: true, numbers: true, symbols: false, spaces: false);

        $usuario = User::create([
            'id_persona' => $persona->id_persona,
            'usuario'    => $username,
            'password'   => Hash::make($plainPassword),
        ]);

        // 5. GENERAR Y TRANSMITIR EL PDF DIRECTAMENTE AL NAVEGADOR
        $data = [
            'titulo'        => 'COMPROBANTE DE CREDENCIALES DE ACCESO',
            'usuario'       => $usuario,
            'persona'       => $persona,
            'plainPassword' => $plainPassword,
            'fecha'         => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.credenciales', $data);

        return $pdf->stream('credenciales-' . $username . '.pdf');
    }

    /**
     * Genera una contraseña nueva y actualiza dentro de una transacción los hashes de users y cuenta.
     * Luego arma los datos de identificación y rol y transmite el comprobante PDF al navegador.
     */
    public function regenerarCredenciales($id_persona)
    {
        $usuario = User::with('persona', 'cuenta.rol')->findOrFail($id_persona);
        $persona = $usuario->persona;

        $newPlainPassword = Str::password(8, letters: true, numbers: true, symbols: false, spaces: false);
        $passwordHash = Hash::make($newPlainPassword);

        // El login autentica contra users, pero cuenta conserva la credencial asociada al rol.
        // La transacción evita que ambas tablas queden con contraseñas distintas si falla una escritura.
        DB::transaction(function () use ($usuario, $passwordHash) {
            $usuario->update(['password' => $passwordHash]);
            if ($usuario->cuenta) {
                $usuario->cuenta->update(['password' => $passwordHash]);
            }
        });

        $data = [
            'titulo'        => 'REGENERACIÓN DE CREDENCIALES DE ACCESO',
            'user'          => (object) [
                'name'     => trim($persona->nombre . ' ' . $persona->primerapellido . ' ' . ($persona->segundoapellido ?? '')),
                'ci'       => $persona->ci,
                'role'     => $usuario->cuenta?->rol?->nombre ?? '-',
                'carrera'  => '-',
                'email'    => $persona->email,
                'username' => $usuario->usuario,
            ],
            'plainPassword' => $newPlainPassword,
            'fecha'         => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.credenciales', $data);

        return $pdf->stream('credenciales-' . $usuario->usuario . '.pdf');
    }

    /**
     * Busca usuario, persona y cuenta, y carga los roles que se pueden seleccionar.
     * Incluye el rol ya asignado aunque esté inactivo para permitir conservarlo al editar otros datos.
     */
    public function edit($id_persona)
    {
        $usuario = User::with('persona', 'cuenta.rol')->findOrFail($id_persona);
        $roles = Rol::query()
            ->where('activo', true)
            ->orWhere('id_rol', $usuario->cuenta?->id_rol)
            ->orderBy('nombre')
            ->get();

        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    /**
     * Valida unicidad de email y username y admite el rol actual aunque esté desactivado.
     * Actualiza persona y usuario; modifica la cuenta existente o la crea cuando falta el vínculo.
     */
    public function update(Request $request, $id_persona)
    {
        $usuario = User::findOrFail($id_persona);

        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:personas,email,' . $id_persona . ',id_persona',
            'usuario' => 'required|string|max:255|unique:users,usuario,' . $id_persona . ',id_persona',
            'id_rol' => [
                'required',
                \Illuminate\Validation\Rule::exists('rol', 'id_rol')
                    ->where(fn ($query) => $query
                        ->where('activo', true)
                        ->orWhere('id_rol', $usuario->cuenta?->id_rol)),
            ],
        ]);

        // Actualizar email en personas
        $usuario->persona->update(['email' => $validated['email']]);

        // Actualizar usuario en users
        $usuario->update(['usuario' => $validated['usuario']]);

        // Actualizar rol en cuenta (o crear si no existe)
        if ($usuario->cuenta) {
            $usuario->cuenta->update(['id_rol' => $validated['id_rol']]);
        } else {
            \App\Models\Cuenta::create([
                'id_persona' => $id_persona,
                'id_rol' => $validated['id_rol'],
                'usuario' => $validated['usuario'],
                'password' => Hash::make(Str::password(8)),
            ]);
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }
}