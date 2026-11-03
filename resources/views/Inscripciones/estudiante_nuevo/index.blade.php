@extends('adminlte::page')

@section('title', 'Estudiante Nuevo')

@section('content_header')
    <h1 class="m-0 text-dark">Estudiante Nuevo</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        
        @include('partials.sweetalert-messages')

        <form action="{{ route('estudiante_nuevo.store') }}" method="POST" enctype="multipart/form-data" id="formEstudianteNuevo" data-student-lookup-url="{{ route('estudiante_nuevo.buscar') }}" novalidate>
            @csrf
            
            {{-- SECCIÓN I: DATOS PERSONALES --}}
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-primary">
                        <i class="fas fa-user-circle mr-1"></i> I. Datos Personales
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="nombre" class="font-weight-bold">Nombre: <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="primerapellido" class="font-weight-bold">Primer apellido: <span class="text-danger">*</span></label>
                            <input type="text" name="primerapellido" id="primerapellido" class="form-control @error('primerapellido') is-invalid @enderror" value="{{ old('primerapellido') }}" required>
                            @error('primerapellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="segundoapellido" class="font-weight-bold">Segundo apellido:</label>
                            <input type="text" name="segundoapellido" id="segundoapellido" class="form-control @error('segundoapellido') is-invalid @enderror" value="{{ old('segundoapellido') }}">
                            @error('segundoapellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="ci" class="font-weight-bold">Carnet de identidad (CI): <span class="text-danger">*</span></label>
                            <input type="text" name="ci" id="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci') }}" required>
                            <small id="estudianteExistenteMensaje" class="form-text text-info d-none"></small>
                            @error('ci')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="lugarexpedicion" class="font-weight-bold">Lugar expedición:</label>
                            <select name="lugarexpedicion" id="lugarexpedicion" class="form-control custom-select @error('lugarexpedicion') is-invalid @enderror">
                                <option value="">-- Seleccionar --</option>
                                @foreach(['CB','LP','SC','OR','PT','CH','TJ','BE','PD','OTRO'] as $dept)
                                    <option value="{{ $dept }}" @selected(old('lugarexpedicion') == $dept)>{{ $dept }}</option>
                                @endforeach
                            </select>
                            @error('lugarexpedicion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="fechanacimiento" class="font-weight-bold">Fecha de nacimiento: <span class="text-danger">*</span></label>
                            <input type="date" name="fechanacimiento" id="fechanacimiento" class="form-control @error('fechanacimiento') is-invalid @enderror" value="{{ old('fechanacimiento') }}" required>
                            @error('fechanacimiento')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="lugarnacimiento" class="font-weight-bold">Lugar de nacimiento: <span class="text-danger">*</span></label>
                            <input type="text" name="lugarnacimiento" id="lugarnacimiento" class="form-control @error('lugarnacimiento') is-invalid @enderror" value="{{ old('lugarnacimiento') }}" required>
                            @error('lugarnacimiento')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="nacionalidad" class="font-weight-bold">Nacionalidad: <span class="text-danger">*</span></label>
                            <input type="text" name="nacionalidad" id="nacionalidad" class="form-control @error('nacionalidad') is-invalid @enderror" value="{{ old('nacionalidad', 'Boliviana') }}" required>
                            @error('nacionalidad')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="sexo" class="font-weight-bold">Sexo: <span class="text-danger">*</span></label>
                            <select name="sexo" id="sexo" class="form-control custom-select @error('sexo') is-invalid @enderror" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="M" @selected(old('sexo') == 'M')>Masculino</option>
                                <option value="F" @selected(old('sexo') == 'F')>Femenino</option>
                            </select>
                            @error('sexo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="direccion" class="font-weight-bold">Dirección: <span class="text-danger">*</span></label>
                            <input type="text" name="direccion" id="direccion" class="form-control @error('direccion') is-invalid @enderror" value="{{ old('direccion') }}" required>
                            @error('direccion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="telefono" class="font-weight-bold">Teléfono:</label>
                            <input type="number" name="telefono" id="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono') }}">
                            @error('telefono')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="celular" class="font-weight-bold">Celular:</label>
                            <input type="number" name="celular" id="celular" class="form-control @error('celular') is-invalid @enderror" value="{{ old('celular') }}">
                            @error('celular')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="email" class="font-weight-bold">Email: <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN II: DATOS DE CONTACTO Y COLEGIO --}}
            <div class="card card-outline card-info shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-info">
                        <i class="fas fa-address-book mr-1"></i> II. Datos de Contacto
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="nombrecontacto" class="font-weight-bold">Nombre de contacto: <span class="text-danger">*</span></label>
                            <input type="text" name="nombrecontacto" id="nombrecontacto" class="form-control @error('nombrecontacto') is-invalid @enderror" value="{{ old('nombrecontacto') }}" required>
                            @error('nombrecontacto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="celularcontacto" class="font-weight-bold">Celular de contacto: <span class="text-danger">*</span></label>
                            <input type="number" name="celularcontacto" id="celularcontacto" class="form-control @error('celularcontacto') is-invalid @enderror" value="{{ old('celularcontacto') }}" required>
                            @error('celularcontacto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="parentescocontacto" class="font-weight-bold">Parentesco contacto: <span class="text-danger">*</span></label>
                            <input type="text" name="parentescocontacto" id="parentescocontacto" class="form-control @error('parentescocontacto') is-invalid @enderror" value="{{ old('parentescocontacto') }}" required>
                            @error('parentescocontacto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="nombrecolegio" class="font-weight-bold">Nombre colegio: <span class="text-danger">*</span></label>
                            <input type="text" name="nombrecolegio" id="nombrecolegio" class="form-control @error('nombrecolegio') is-invalid @enderror" value="{{ old('nombrecolegio') }}" required>
                            @error('nombrecolegio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="caractercolegio" class="font-weight-bold">Carácter colegio: <span class="text-danger">*</span></label>
                            <select name="caractercolegio" id="caractercolegio" class="form-control custom-select @error('caractercolegio') is-invalid @enderror" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="Fiscal" @selected(old('caractercolegio') == 'Fiscal')>Fiscal</option>
                                <option value="Particular" @selected(old('caractercolegio') == 'Particular')>Particular</option>
                                <option value="Convenio" @selected(old('caractercolegio') == 'Convenio')>Convenio</option>
                            </select>
                            @error('caractercolegio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="egresocolegio" class="font-weight-bold">Año de egreso colegio: <span class="text-danger">*</span></label>
                            <input type="number" name="egresocolegio" id="egresocolegio" class="form-control @error('egresocolegio') is-invalid @enderror" value="{{ old('egresocolegio', date('Y')) }}" required>
                            @error('egresocolegio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN III: DATOS DE INSCRIPCIÓN --}}
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-success">
                        <i class="fas fa-file-signature mr-1"></i> III. Datos de Inscripción
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="fecha" class="font-weight-bold">Fecha: <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" id="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', date('Y-m-d')) }}" required>
                            @error('fecha')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="diplomabachiller" class="font-weight-bold">Diploma bachiller: <span class="text-danger">*</span></label>
                            <select name="diplomabachiller" id="diplomabachiller" class="form-control custom-select @error('diplomabachiller') is-invalid @enderror" required>
                                <option value="0" @selected(old('diplomabachiller', '0') == '0')>No</option>
                                <option value="1" @selected(old('diplomabachiller') == '1')>Sí</option>
                            </select>
                            @error('diplomabachiller')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="becado" class="font-weight-bold">Becado:</label>
                            <select name="becado" id="becado" class="form-control custom-select @error('becado') is-invalid @enderror">
                                <option value="0" @selected(old('becado', '0') == '0')>No</option>
                                <option value="1" @selected(old('becado') == '1')>Sí</option>
                            </select>
                            @error('becado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="deposito" class="font-weight-bold">N.º de comprobante / voucher: <span class="text-danger">*</span></label>
                            <input type="text" name="deposito" id="deposito" class="form-control @error('deposito') is-invalid @enderror" value="{{ old('deposito') }}" required>
                            @error('deposito')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="id_gestionacademica" class="font-weight-bold">Gestión académica: <span class="text-danger">*</span></label>
                            <select name="id_gestionacademica" id="id_gestionacademica" class="form-control custom-select @error('id_gestionacademica') is-invalid @enderror" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($gestiones as $g)
                                    <option value="{{ $g->id_gestionacademica }}" @selected(old('id_gestionacademica') == $g->id_gestionacademica)>
                                        Gestión {{ $g->gestion }} - Periodo {{ $g->periodo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_gestionacademica')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="traspaso_convalidacion" class="font-weight-bold">Traspaso/Convalidación:</label>
                            <select name="traspaso_convalidacion" id="traspaso_convalidacion" class="form-control custom-select @error('traspaso_convalidacion') is-invalid @enderror">
                                <option value="0" @selected(old('traspaso_convalidacion', '0') == '0')>No</option>
                                <option value="1" @selected(old('traspaso_convalidacion') == '1')>Sí</option>
                            </select>
                            @error('traspaso_convalidacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="id_carrera" class="font-weight-bold">Carrera: <span class="text-danger">*</span></label>
                            <select name="id_carrera" id="id_carrera" class="form-control custom-select @error('id_carrera') is-invalid @enderror" required>
                                <option value="">-- Seleccionar Carrera --</option>
                                @foreach($carreras as $c)
                                    @php
                                        $resoluciones = $c->resolucionesMinisteriales();
                                    @endphp
                                    <option value="{{ $c->id_carrera }}" @selected(old('id_carrera') == $c->id_carrera)>
                                        {{ $c->nombre }}
                                        @if($resoluciones)
                                            · R.M. {{ implode(' / ', $resoluciones) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('id_carrera')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="nivelinicio" class="font-weight-bold">Año que cursará: <span class="text-danger">*</span></label>
                            <select name="nivelinicio" id="nivelinicio" class="form-control custom-select @error('nivelinicio') is-invalid @enderror" required>
                                @foreach (range(1, 3) as $nivel)
                                    <option value="{{ $nivel }}" @selected(old('nivelinicio', 1) == $nivel)>{{ ['Primer', 'Segundo', 'Tercer'][$nivel - 1] }} año</option>
                                @endforeach
                            </select>
                            @error('nivelinicio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>
                    <h4 class="font-weight-bold mb-3">IV. Documentación digital requerida</h4>
                    <div class="row">
                        @php
                            $documentos = [
                                'path_ci_frontal' => ['Carnet de identidad, anverso', 'CI frontal nítido', false],
                                'path_ci_reverso' => ['Carnet de identidad, reverso', 'CI reverso nítido', false],
                                'path_nacimiento' => ['Certificado de nacimiento', 'Certificado nítido', false],
                                'path_diploma' => ['Diploma de bachiller', 'Diploma nítido', false],
                                'path_bth' => ['Certificado BTH', 'Solo para estudiantes que cuentan con este certificado', true],
                            ];
                            @endphp
                            @foreach($documentos as $campo => [$etiqueta, $descripcion, $opcional])
                                <div class="col-md-6 mb-3">
                                    <label for="{{ $campo }}" class="font-weight-bold">
                                        {{ $etiqueta }}:
                                        @if ($opcional)
                                            <span class="text-muted">(opcional)</span>
                                        @else
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                    <div class="custom-file">
                                        <input type="file" name="{{ $campo }}" id="{{ $campo }}" class="custom-file-input @error($campo) is-invalid @enderror" accept="image/jpeg,image/png,image/webp" data-name="{{ $etiqueta }}" @unless ($opcional) required @endunless>
                                        <label class="custom-file-label" for="{{ $campo }}" data-browse="Elegir">Seleccionar imagen...</label>
                                    </div>
                                @error($campo)
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-1">{{ $descripcion }} · Máximo 350 KB después de comprimir</small>
                                <div id="preview_{{ str_replace('path_', '', $campo) }}" class="mt-2 text-center d-none border rounded p-2">
                                    <img src="" class="img-fluid d-none" style="max-height: 220px;" alt="Vista previa: {{ $etiqueta }}">
                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                        <span class="file-name text-truncate font-weight-bold mr-2"></span>
                                        <button type="button" class="btn btn-xs btn-outline-danger btn-remove-doc" data-target="{{ $campo }}">
                                            <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('documentos')
                        <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror
                    <div class="alert alert-info mb-0">
                        Se aceptan imágenes JPG, PNG o WebP. Cada documento se comprime a un máximo de 350 KB; el total no debe superar 1.750 KB.
                    </div>
                </div>

                <div class="card-footer bg-light">
                    <button type="submit" id="btnGuardar" class="btn btn-primary font-weight-bold mr-2 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <a href="{{ route('estudiante_nuevo.index') }}" class="btn btn-secondary font-weight-bold shadow-sm">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                </div>
            </div>

        </form>

    </div>
</div>
@stop

@push('js')
<script src="{{ asset('js/estudiante-nuevo-form.js') }}"></script>
@endpush