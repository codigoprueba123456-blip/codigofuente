<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Recurso;
use App\Models\Rol;
use App\Support\MenuResourceSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RolController extends Controller
{
	public function index(Request $request)
	{
		$buscar = trim((string) $request->input('q', ''));
		$roles = Rol::with('recursos')
			->when($buscar !== '', fn ($query) => $query->where(function ($query) use ($buscar): void {
				$term = '%'.$buscar.'%';
				$query->where('nombre', 'like', $term)
					->orWhereHas('recursos', fn ($recurso) => $recurso->where('nombre', 'like', $term));
			}))
			->get();

		return view('admin.roles.index', compact('roles'));
	}

	public function create()
	{
		MenuResourceSync::sync();
		$recursos = Recurso::orderBy('nombre')->get();
		$menu = config('adminlte.menu', []);

		return view('admin.roles.create', compact('recursos', 'menu'));
	}

	public function store(Request $request)
	{
		$validated = $request->validate([
			'nombre' => ['required', 'string', 'max:255', 'unique:rol,nombre'],
			'recursos' => ['sometimes', 'array'],
			'recursos.*' => ['integer', 'exists:recurso,id_recurso'],
		]);

		$rol = Rol::create(['nombre' => $validated['nombre']]);
		$rol->recursos()->sync($validated['recursos'] ?? []);

		return redirect()->route('admin.roles.index')
			->with('success', 'Rol creado correctamente.');
	}

	public function edit($id)
	{
		MenuResourceSync::sync();
		$rol = Rol::with('recursos')->findOrFail($id);
		$recursos = Recurso::orderBy('nombre')->get();
		$menu = config('adminlte.menu', []);

		return view('admin.roles.edit', compact('rol', 'recursos', 'menu'));
	}

	public function update(Request $request, $id)
	{
		$rol = Rol::findOrFail($id);

		$validated = $request->validate([
			'nombre' => [
				'required',
				'string',
				'max:255',
				Rule::unique('rol', 'nombre')->ignore($rol->id_rol, 'id_rol'),
			],
			'recursos' => ['sometimes', 'array'],
			'recursos.*' => ['integer', 'exists:recurso,id_recurso'],
		]);

		$rol->update(['nombre' => $validated['nombre']]);
		$rol->recursos()->sync($validated['recursos'] ?? []);

		return redirect()->route('admin.roles.index')
			->with('success', 'Rol actualizado correctamente.');
	}

	public function cambiarEstado(Request $request, $id)
	{
		$validated = $request->validate([
			'activo' => ['required', 'boolean'],
		]);
		$rol = Rol::findOrFail($id);
		if (! $validated['activo'] && (int) Auth::user()?->cuenta?->id_rol === (int) $rol->id_rol) {
			throw ValidationException::withMessages([
				'activo' => 'No puedes deshabilitar el rol asociado a tu propia cuenta.',
			]);
		}
		$rol->update(['activo' => $validated['activo']]);

		return redirect()->route('admin.roles.index')
			->with('success', 'Estado del rol actualizado correctamente.');
	}
}
