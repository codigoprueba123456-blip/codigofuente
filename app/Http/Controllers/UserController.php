<?php

namespace App\Http\Controllers;

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
    public function index()
    {
        $usuarios = User::with('persona', 'cuenta.rol')->get();
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        
    }

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
     */
    public function regenerarCredenciales($id_persona)
    {
        $usuario = User::with('persona', 'cuenta.rol')->findOrFail($id_persona);
        $persona = $usuario->persona;

        $newPlainPassword = Str::password(8, letters: true, numbers: true, symbols: false, spaces: false);
        $passwordHash = Hash::make($newPlainPassword);

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
                'ci'       => $persona->dni,
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

    public function destroy($id_persona)
    {
        $usuario = User::findOrFail($id_persona);
        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function edit($id_persona)
    {
        $usuario = User::with('persona', 'cuenta.rol')->findOrFail($id_persona);
        $roles = Rol::orderBy('nombre')->get();

        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, $id_persona)
    {
        $usuario = User::findOrFail($id_persona);

        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:personas,email,' . $id_persona . ',id_persona',
            'usuario' => 'required|string|max:255|unique:users,usuario,' . $id_persona . ',id_persona',
            'id_rol' => 'required|exists:rol,id_rol',
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