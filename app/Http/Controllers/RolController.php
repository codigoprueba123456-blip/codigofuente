<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use App\Models\Rol;
use App\Support\MenuResourceSync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
	public function index()
	{
		$roles = Rol::with('recursos')->get();

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

	public function destroy($id)
	{
		$rol = Rol::findOrFail($id);
		$rol->delete();

		return redirect()->route('admin.roles.index')
			->with('success', 'Rol eliminado correctamente.');
	}
}
