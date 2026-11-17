<?php
use App\Http\Controllers\Admisiones\AdmisionesController;
use App\Http\Controllers\Estudiantes\EstudiantePortalController;
use App\Http\Controllers\Inscripciones\EstudianteNuevoController;
use App\Http\Controllers\Inscripciones\EstudianteNuevoPostulacionController;
use App\Http\Controllers\Inscripciones\EstudianteRegularController;
use App\Http\Controllers\Inscripciones\CambioCarreraController;
use App\Http\Controllers\Inscripciones\InscripcionManualController;
use App\Http\Controllers\Inscripciones\InscritosController;
use App\Http\Controllers\Pagos\PagosController;
use App\Http\Controllers\Reportes\InscripcionesDashboardController;
use App\Http\Controllers\Reportes\InscripcionesReportesController;
use App\Http\Controllers\Postulantes\PostulanteController;
use App\Http\Middleware\EnsurePublicPostulacionAbierta;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Academico\ActividadController;
use App\Http\Controllers\Academico\AsignacionDocenteController;
use App\Http\Controllers\Academico\CarreraController;
use App\Http\Controllers\Academico\GestionAcademicaController;
use App\Http\Controllers\Academico\GrupoController;
// INICIO: Módulo temporal de organización del proceso de inscripción.
use App\Http\Controllers\Academico\InicioInscripcionController;
// FIN: Módulo temporal de organización del proceso de inscripción.
use App\Http\Controllers\Academico\MateriaController;
use App\Http\Controllers\Administracion\EmpleadoController;
use App\Http\Controllers\Administracion\InstitutoController;
use App\Http\Controllers\Administracion\RecursoController;
use App\Http\Controllers\Administracion\RolController;
use App\Http\Controllers\Administracion\UserController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Reportes\MallaCurricularReporteController;

Route::get('/', function () {
    $carreras = \App\Models\Carrera::activas()->orderBy('nombre')->get();

    return view('welcome', compact('carreras'));
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/reportes/dashboard', [InscripcionesDashboardController::class, 'index'])
    ->middleware('auth')
    ->name('reportes.dashboard');

Route::middleware(['auth', 'resource.access'])->group(function () {
//Configuracion de instituto 
Route::get('/admin/instituto', [InstitutoController::class, 'index'])->name('admin.instituto.index')->middleware('auth');
Route::put('/admin/instituto', [InstitutoController::class, 'update'])->name('admin.instituto.update')->middleware('auth');
//rutas para los empleados
Route::get('/admin/empleados', [EmpleadoController::class, 'index'])->name('admin.empleados.index')->middleware('auth');
Route::get('/admin/empleados/create', [EmpleadoController::class, 'create'])->name('admin.empleados.create')->middleware('auth');
Route::post('/admin/empleados/create', [EmpleadoController::class, 'store'])->name('admin.empleados.store')->middleware('auth');
Route::get('/admin/empleados/{id}/edit', [EmpleadoController::class, 'edit'])->name('admin.empleados.edit')->middleware('auth');
Route::put('/admin/empleados/{id}', [EmpleadoController::class, 'update'])->name('admin.empleados.update')->middleware('auth');
Route::patch('/admin/empleados/{id}/estado', [EmpleadoController::class, 'cambiarEstado'])->name('admin.empleados.estado')->middleware('auth');
//rutas para los usuarios
Route::get('/admin/usuarios', [UserController::class, 'index'])->name('admin.usuarios.index')->middleware('auth');
Route::get('/admin/usuarios/{id}/edit', [UserController::class, 'edit'])->name('admin.usuarios.edit')->middleware('auth');
Route::put('/admin/usuarios/{id}', [UserController::class, 'update'])->name('admin.usuarios.update')->middleware('auth');
Route::post('/admin/usuarios/{id}/regenerar-credenciales', [UserController::class, 'regenerarCredenciales'])->name('admin.usuarios.regenerar-credenciales')->middleware('auth');
//rutas para los roles
Route::get('/admin/roles', [RolController::class, 'index'])->name('admin.roles.index')->middleware('auth');
Route::get('/admin/roles/create', [RolController::class, 'create'])->name('admin.roles.create')->middleware('auth');
Route::post('/admin/roles/create', [RolController::class, 'store'])->name('admin.roles.store')->middleware('auth');
Route::get('/admin/roles/{id}/edit', [RolController::class, 'edit'])->name('admin.roles.edit')->middleware('auth');
Route::put('/admin/roles/{id}', [RolController::class, 'update'])->name('admin.roles.update')->middleware('auth');
Route::patch('/admin/roles/{id}/estado', [RolController::class, 'cambiarEstado'])->name('admin.roles.estado')->middleware('auth');
//rutas para los recursos
Route::get('/admin/recursos', [RecursoController::class, 'index'])->name('admin.recursos.index')->middleware('auth');
Route::get('/admin/recursos/create', [RecursoController::class, 'create'])->name('admin.recursos.create')->middleware('auth');
Route::post('/admin/recursos/create', [RecursoController::class, 'store'])->name('admin.recursos.store')->middleware('auth');
//rutas para las carreras
Route::get('/planes/carreras', [CarreraController::class, 'index'])->name('planes.carreras.index')->middleware('auth');
Route::get('/planes/carreras/create', [CarreraController::class, 'create'])->name('planes.carreras.create')->middleware('auth');
Route::post('/planes/carreras/create', [CarreraController::class, 'store'])->name('planes.carreras.store')->middleware('auth');
Route::get('/planes/carreras/{id}/materias/import', [MateriaController::class, 'importForm'])->name('planes.carreras.materias.import')->middleware('auth');
Route::post('/planes/carreras/{id}/materias/import', [MateriaController::class, 'import'])->name('planes.carreras.materias.import.store')->middleware('auth');
Route::get('/planes/carreras/{id}/edit', [CarreraController::class, 'edit'])->name('planes.carreras.edit')->middleware('auth');
Route::put('/planes/carreras/{id}', [CarreraController::class, 'update'])->name('planes.carreras.update')->middleware('auth');
Route::patch('/planes/carreras/{id}/estado', [CarreraController::class, 'cambiarEstado'])->name('planes.carreras.estado')->middleware('auth');
//rutas para materias y prerequisitos
Route::get('/planes/materias', [MateriaController::class, 'index'])->name('planes.materias.index')->middleware('auth');
Route::get('/planes/materias/create', [MateriaController::class, 'create'])->name('planes.materias.create')->middleware('auth');
Route::post('/planes/materias', [MateriaController::class, 'store'])->name('planes.materias.store')->middleware('auth');
Route::get('/planes/materias/{id}/edit', [MateriaController::class, 'edit'])->name('planes.materias.edit')->middleware('auth');
Route::put('/planes/materias/{id}', [MateriaController::class, 'update'])->name('planes.materias.update')->middleware('auth');
Route::patch('/planes/materias/{id}/estado', [MateriaController::class, 'cambiarEstado'])->name('planes.materias.estado')->middleware('auth');
// INICIO: Módulo temporal de organización del proceso de inscripción.
Route::get('/planes/gestiones/inicio-inscripcion', [InicioInscripcionController::class, 'index'])
    ->name('inicio_inscripcion.index');
Route::get('/planes/gestiones/inicio-inscripcion/{gestionId}', [InicioInscripcionController::class, 'gestion'])
    ->whereNumber('gestionId')
    ->name('inicio_inscripcion.gestion');
Route::get('/planes/gestiones/inicio-inscripcion/{gestionId}/carreras/{carreraId}', [InicioInscripcionController::class, 'carrera'])
    ->whereNumber('gestionId')
    ->whereNumber('carreraId')
    ->name('inicio_inscripcion.carrera');
Route::get('/planes/gestiones/inicio-inscripcion/{gestionId}/carreras/{carreraId}/paralelos/{paralelo}/turnos/{turno}', [InicioInscripcionController::class, 'paralelo'])
    ->whereNumber('gestionId')
    ->whereNumber('carreraId')
    ->where('paralelo', '[^/]+')
    ->whereNumber('turno')
    ->name('inicio_inscripcion.paralelo');
Route::get('/planes/gestiones/inicio-inscripcion/{gestionId}/carreras/{carreraId}/paralelos/{paralelo}/turnos/{turno}/materias/{materiaId}', [InicioInscripcionController::class, 'materia'])
    ->whereNumber('gestionId')
    ->whereNumber('carreraId')
    ->where('paralelo', '[^/]+')
    ->whereNumber('turno')
    ->whereNumber('materiaId')
    ->name('inicio_inscripcion.materia');
// FIN: Módulo temporal de organización del proceso de inscripción.
//rutas para gestiones académicas
Route::get('/planes/gestiones', [GestionAcademicaController::class, 'index'])->name('admin.gestionacademica.index')->middleware('auth');
Route::get('/planes/gestiones/create', [GestionAcademicaController::class, 'create'])->name('admin.gestionacademica.create')->middleware('auth');
Route::post('/planes/gestiones', [GestionAcademicaController::class, 'store'])->name('admin.gestionacademica.store')->middleware('auth');
Route::get('/planes/gestiones/{id}/edit', [GestionAcademicaController::class, 'edit'])->name('admin.gestionacademica.edit')->middleware('auth');
Route::put('/planes/gestiones/{id}', [GestionAcademicaController::class, 'update'])->name('admin.gestionacademica.update')->middleware('auth');
Route::patch('/planes/gestiones/{id}/estado', [GestionAcademicaController::class, 'cambiarEstado'])->name('admin.gestionacademica.estado')->middleware('auth');
//rutas para calendario y actividades
Route::get('/planes/calendario', [ActividadController::class, 'index'])->name('admin.actividad.index')->middleware('auth');
Route::get('/planes/calendario/create', [ActividadController::class, 'create'])->name('admin.actividad.create')->middleware('auth');
Route::post('/planes/calendario', [ActividadController::class, 'store'])->name('admin.actividad.store')->middleware('auth');
Route::get('/planes/calendario/{id}/edit', [ActividadController::class, 'edit'])->name('admin.actividad.edit')->middleware('auth');
Route::put('/planes/calendario/{id}', [ActividadController::class, 'update'])->name('admin.actividad.update')->middleware('auth');
Route::patch('/planes/calendario/{id}/estado', [ActividadController::class, 'cambiarEstado'])->name('admin.actividad.estado')->middleware('auth');
//rutas para grupos
Route::get('/planes/grupos', [GrupoController::class, 'index'])->name('admin.grupos.index')->middleware('auth');
Route::get('/planes/grupos/programar', [GrupoController::class, 'programar'])->name('admin.grupos.programar')->middleware('auth');
Route::post('/planes/grupos', [GrupoController::class, 'store'])->name('admin.grupos.store')->middleware('auth');
Route::get('/planes/grupos/{id}/edit', [GrupoController::class, 'edit'])->name('admin.grupos.edit')->middleware('auth');
Route::put('/planes/grupos/{id}', [GrupoController::class, 'update'])->name('admin.grupos.update')->middleware('auth');
Route::patch('/planes/grupos/{id}/estado', [GrupoController::class, 'cambiarEstado'])->name('admin.grupos.estado')->middleware('auth');
// Rutas para la asignación docente
Route::get('/planes/asignacion-docente', [AsignacionDocenteController::class, 'index'])->name('admin.asignacion-docente.index')->middleware('auth');
Route::get('/planes/asignacion-docente/create', [AsignacionDocenteController::class, 'create'])->name('admin.asignacion-docente.create')->middleware('auth');
Route::get('/planes/asignacion-docente/{id}/edit', [AsignacionDocenteController::class, 'edit'])->name('admin.asignacion-docente.edit')->middleware('auth');
Route::put('/planes/asignacion-docente/{id}', [AsignacionDocenteController::class, 'asignar'])->name('admin.asignacion-docente.asignar')->middleware('auth');
});

Route::middleware('auth')->group(function () {
    Route::get('/cuenta/cambiar-contrasena', [ChangePasswordController::class, 'edit'])
        ->name('account.password.edit');
    Route::put('/cuenta/cambiar-contrasena', [ChangePasswordController::class, 'update'])
        ->name('account.password.update');
});

Route::prefix('postulantes')->name('postulantes.')->group(function () {
    Route::get('/', [PostulanteController::class, 'bienvenida'])->name('bienvenida');
    Route::get('/{id}/pdf', [PostulanteController::class, 'imprimir'])->whereNumber('id')->name('pdf');

    Route::middleware(EnsurePublicPostulacionAbierta::class)->group(function () {
        Route::get('/registrar', [PostulanteController::class, 'create'])->name('registrar');
        Route::post('/store', [PostulanteController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [PostulanteController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PostulanteController::class, 'update'])->name('update');
        Route::delete('/{id}', [PostulanteController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
});

Route::middleware(['auth', 'resource.access'])->prefix('admisiones/postulantes')->name('admisiones.postulantes.')->group(function () {
    Route::get('/', [AdmisionesController::class, 'index'])->name('index');

    Route::get('/excel', [AdmisionesController::class, 'excel'])->name('excel');
    Route::get('/pdf', [AdmisionesController::class, 'pdf'])->name('pdf');
});

// Rutas para el Módulo de Estudiante Nuevo
Route::get('/inscripciones/estudiante_nuevo', [EstudianteNuevoController::class, 'index'])->name('estudiante_nuevo.index')->middleware(['auth', 'resource.access']);
Route::get('/inscripciones/estudiante_nuevo/buscar', [EstudianteNuevoController::class, 'buscar'])->name('estudiante_nuevo.buscar')->middleware(['auth', 'resource.access']);
Route::post('/inscripciones/estudiante_nuevo', [EstudianteNuevoController::class, 'store'])->name('estudiante_nuevo.store')->middleware(['auth', 'resource.access']);

// Rutas para la inscripción desde postulante
Route::get('/inscripciones/estudiante_nuevo_postulacion', [EstudianteNuevoPostulacionController::class, 'index'])->name('estudiante_nuevo_postulacion.index')->middleware(['auth', 'resource.access']);
Route::get('/inscripciones/estudiante_nuevo_postulacion/buscar', [EstudianteNuevoPostulacionController::class, 'buscar'])->name('estudiante_nuevo_postulacion.buscar')->middleware(['auth', 'resource.access']);
Route::post('/inscripciones/estudiante_nuevo_postulacion', [EstudianteNuevoPostulacionController::class, 'store'])->name('estudiante_nuevo_postulacion.store')->middleware(['auth', 'resource.access']);

// Ruta para estudiante regular
Route::get('/inscripciones/estudiante_regular', [EstudianteRegularController::class, 'index'])->name('estudiante_regular.index')->middleware(['auth', 'resource.access']);
Route::get('/inscripciones/estudiante_regular/buscar', [EstudianteRegularController::class, 'buscar'])->name('estudiante_regular.buscar')->middleware(['auth', 'resource.access']);
Route::get('/inscripciones/estudiante_regular/turnos', [EstudianteRegularController::class, 'opcionesTurnos'])->name('estudiante_regular.turnos')->middleware(['auth', 'resource.access']);
Route::post('/inscripciones/estudiante_regular', [EstudianteRegularController::class, 'store'])->name('estudiante_regular.store')->middleware(['auth', 'resource.access']);

// Ruta para inscritos
Route::get('/inscripciones/inscriptos', [InscritosController::class, 'index'])->name('inscriptos.index')->middleware(['auth', 'resource.access']);
Route::get('/inscripciones/inscriptos/{inscrito}/editar', [InscritosController::class, 'edit'])->name('inscriptos.edit')->middleware(['auth', 'resource.access']);
Route::put('/inscripciones/inscriptos/{inscrito}', [InscritosController::class, 'update'])->name('inscriptos.update')->middleware(['auth', 'resource.access']);
Route::post('/inscripciones/inscriptos/{inscrito}/deshabilitar', [InscritosController::class, 'deshabilitar'])->name('inscriptos.deshabilitar')->middleware(['auth', 'resource.access']);

Route::get('/inscripcion/nuevo', [EstudianteNuevoController::class, 'index'])->name('inscripcion.create')->middleware(['auth', 'resource.access']);
Route::post('/inscripcion/nuevo', [EstudianteNuevoController::class, 'store'])->name('inscripcion.store')->middleware(['auth', 'resource.access']);

// Cambio de carrera
Route::get('/inscripciones/cambio_carrera', [CambioCarreraController::class, 'index'])
    ->name('cambio_carrera.index')
    ->middleware(['auth', 'resource.access']);
Route::get('/inscripciones/cambio_carrera/buscar', [CambioCarreraController::class, 'buscarEstudiantes'])
    ->name('cambio_carrera.buscar')
    ->middleware(['auth', 'resource.access']);
Route::post('/inscripciones/cambio_carrera', [CambioCarreraController::class, 'store'])
    ->name('cambio_carrera.store')
    ->middleware(['auth', 'resource.access']);
Route::middleware(['auth', 'resource.access'])->prefix('inscripciones/inscripcion_manual')->name('inscripcion_manual.')->group(function () {
    Route::get('/', [InscripcionManualController::class, 'index'])->name('index');
    Route::get('/buscar', [InscripcionManualController::class, 'buscar'])->name('buscar');
    Route::get('/{inscrito}/oferta', [InscripcionManualController::class, 'oferta'])->name('oferta');
    Route::get('/{inscrito}/materias', [InscripcionManualController::class, 'materias'])->name('materias');
    Route::put('/{inscrito}/grupos', [InscripcionManualController::class, 'guardar'])->name('guardar');
    Route::put('/{inscrito}/turno', [InscripcionManualController::class, 'cambiarTurno'])->name('cambiar-turno');
    Route::delete('/{inscrito}/materias/{materia}', [InscripcionManualController::class, 'retirarMateria'])->name('retirar');
});
Route::get('/pagos/pago', [PagosController::class, 'index'])
    ->name('pagos.index')
    ->middleware(['auth', 'resource.access']);
Route::get('/reportes/malla-curricular', [MallaCurricularReporteController::class, 'index'])
    ->name('reportes.academicos.malla-curricular')
    ->middleware(['auth', 'resource.access']);
Route::get('/reportes/inscripciones/{reporte}', [InscripcionesReportesController::class, 'index'])
    ->where('reporte', 'lista-postulantes|lista-inscritos-carrera|lista-inscritos-paralelo|lista-inscritos-grupo|libro-inscripciones|libro-inscripciones-carrera|lista-inscritos-multicarrera')
    ->name('reportes.inscripciones.index')
    ->middleware(['auth', 'resource.access']);
Route::get('/estudiantes/historial_academico', function () {
    return view('estudiantes.historial_academico.index');
})->middleware(['auth', 'role:estudiante']);
Route::get('/estudiantes/historial_economico', [EstudiantePortalController::class, 'historialEconomico'])
    ->name('estudiantes.historial_economico')
    ->middleware(['auth', 'role:estudiante']);
Route::middleware(['auth', 'role:estudiante'])->prefix('estudiantes')->name('estudiantes.')->group(function () {
    Route::get('/estado_inscripcion', [EstudiantePortalController::class, 'estadoInscripcion'])->name('estado_inscripcion');
    Route::get('/file_estudiante', [EstudiantePortalController::class, 'fileEstudiante'])->name('file_estudiante');
    Route::put('/file_estudiante', [EstudiantePortalController::class, 'actualizarFile'])->name('file_estudiante.update');
});
