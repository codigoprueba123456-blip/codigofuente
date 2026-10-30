@extends('adminlte::page')

@section('title', 'Nuevo rol')

@section('content_header')
	<h1>Nuevo rol</h1>
@stop

@section('content')
	<div class="card card-outline card-secondary">
		<div class="card-header">
			<h3 class="card-title">Registrar rol</h3>
		</div>

		<form action="{{ route('admin.roles.store') }}" method="POST">
			@csrf

			<div class="card-body">
				<div class="form-group">
					<label for="nombre">Nombre del rol</label>
					<input
						type="text"
						id="nombre"
						name="nombre"
						class="form-control @error('nombre') is-invalid @enderror"
						value="{{ old('nombre') }}"
						required
						autofocus
					>
					@error('nombre')
						<span class="invalid-feedback">{{ $message }}</span>
					@enderror
				</div>

				<div class="form-group">
					<label>Recursos del menú</label>
					@php($recursosPorUrl = $recursos->keyBy('urlpattern'))
					@php($recursosSeleccionados = old('recursos', []))
					<select
						id="recursos"
						name="recursos[]"
						class="form-control @error('recursos') is-invalid @enderror"
						multiple
						size="10"
					>
						@include('admin.roles.partials.menu-checkboxes', ['items' => $menu, 'nivel' => 0])
					</select>
					@error('recursos')
						<span class="invalid-feedback">{{ $message }}</span>
					@enderror
					@error('recursos.*')
						<span class="invalid-feedback">{{ $message }}</span>
					@enderror
				</div>
			</div>

			<div class="card-footer">
				<a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
					Cancelar
				</a>
				<button type="submit" class="btn btn-primary">
					<i class="fas fa-save"></i> Guardar
				</button>
			</div>
		</form>
	</div>
@stop
