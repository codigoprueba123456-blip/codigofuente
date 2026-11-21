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
	 * Recupera roles junto con sus permisos y admite una búsqueda parcial por nombre de rol o recurso.
	 * Entrega la colección completa a la vista de administración de roles.
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
	 * Asegura que el catálogo de recursos refleje el menú actual y carga el menú agrupador.
	 * La vista usa ambas fuentes para mostrar permisos por módulo y enlaces independientes.
	 */
	public function create()
	{
		MenuResourceSync::sync();
		$recursos = Recurso::orderBy('nombre')->get();
		$menu = config('adminlte.menu', []);

		return view('admin.roles.create', compact('recursos', 'menu'));
	}

	/**
	 * Valida el nombre único y los IDs de recursos, crea el rol y sincroniza sus permisos.
	 * Una lista vacía es válida y deja el nuevo rol sin recursos asignados.
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
	 * Busca el rol con sus recursos actuales y sincroniza el catálogo antes de mostrar el formulario.
	 * La vista puede comparar los permisos guardados con los que ofrece actualmente el menú.
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
	 * Valida el nombre excluyendo el propio rol de la regla de unicidad y valida cada recurso.
	 * Reemplaza la lista de permisos completa; si no se envían recursos, revoca todas las asociaciones.
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
	 * Cambia el estado del rol según un booleano y bloquea la desactivación del rol propio.
	 * Esto evita que la cuenta actual pierda su autorización mientras administra roles.
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
