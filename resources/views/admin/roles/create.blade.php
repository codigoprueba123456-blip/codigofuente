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
					@php($recursosSeleccionados = old('recursos', []))
					{{-- El partial agrupa por módulo y permite marcar recursos individualmente o en bloque. --}}
					@include('admin.roles.partials.resource-groups', ['menu' => $menu, 'recursos' => $recursos, 'selectedResources' => $recursosSeleccionados])
					@error('recursos')
						<span class="invalid-feedback d-block">{{ $message }}</span>
					@enderror
					@error('recursos.*')
						<span class="invalid-feedback d-block">{{ $message }}</span>
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
