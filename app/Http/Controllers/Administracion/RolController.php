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
	/**
	 * Lista roles con sus recursos y filtra por nombre de rol o nombre de recurso.
	 */
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

	/**
	 * Sincroniza las URLs del menú y prepara los recursos que se pueden asignar al rol.
	 */
	public function create()
	{
		MenuResourceSync::sync();
		$recursos = Recurso::orderBy('nombre')->get();
		$menu = config('adminlte.menu', []);

		return view('admin.roles.create', compact('recursos', 'menu'));
	}

	/**
	 * Valida el nombre y los recursos seleccionados, crea el rol y enlaza sus permisos.
	 */
	public function store(Request $request)
	{
		$validated = $request->validate([
			'nombre' => ['required', 'string', 'max:255', 'unique:rol,nombre'],
			'recursos' => ['sometimes', 'array'],
			'recursos.*' => ['integer', 'exists:recurso,id_recurso'],
		]);

		$rol = Rol::create(['nombre' => $validated['nombre']]);
		// sync guarda exactamente los recursos seleccionados y elimina cualquier asociación anterior.
		$rol->recursos()->sync($validated['recursos'] ?? []);

		return redirect()->route('admin.roles.index')
			->with('success', 'Rol creado correctamente.');
	}

	/**
	 * Carga el rol con sus permisos y prepara el catálogo para cambiar esas asignaciones.
	 */
	public function edit($id)
	{
		MenuResourceSync::sync();
		$rol = Rol::with('recursos')->findOrFail($id);
		$recursos = Recurso::orderBy('nombre')->get();
		$menu = config('adminlte.menu', []);

		return view('admin.roles.edit', compact('rol', 'recursos', 'menu'));
	}

	/**
	 * Actualiza el nombre y reemplaza por completo la lista de recursos asignados.
	 */
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
		// En edición se reemplazan las asociaciones existentes; si no se marca ninguno,
		// la lista vacía revoca todos los recursos del rol.
		$rol->recursos()->sync($validated['recursos'] ?? []);

		return redirect()->route('admin.roles.index')
			->with('success', 'Rol actualizado correctamente.');
	}

	/**
	 * Cambia el estado del rol y evita que el usuario actual deshabilite su propio rol.
	 */
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
