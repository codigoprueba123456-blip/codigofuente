<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagosController extends Controller
{
    /**
     * Lista los pagos existentes, aplica los filtros solicitados y prepara
     * la vista o el archivo de exportación. Este controlador es de consulta:
     * no crea ni modifica pagos.
     */
    public function index(Request $request)
    {
        // Validar filtros y opciones de presentación antes de construir la consulta.
        $filters = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'usuario' => ['nullable', 'string', 'max:100'],
            'buscar' => ['nullable', 'string', 'max:100'],
            'por_pagina' => ['nullable', 'integer', 'in:10,25,50'],
            'formato' => ['nullable', 'in:excel,pdf'],
        ]);

        $query = $this->pagosQuery();

        // El período usa la primera fecha disponible del comprobante,
        // la inscripción, la postulación o, como último recurso, la creación del pago.
        if (! empty($filters['desde'])) {
            $query->whereDate(
                DB::raw('COALESCE(cb.fecha, i.fecha, po.fecha, pago.created_at)'),
                '>=',
                $filters['desde']
            );
        }

        if (! empty($filters['hasta'])) {
            $query->whereDate(
                DB::raw('COALESCE(cb.fecha, i.fecha, po.fecha, pago.created_at)'),
                '<=',
                $filters['hasta']
            );
        }

        // Cada término debe coincidir con algún dato de identidad o acceso
        // del estudiante/postulante o de quien registró el comprobante.
        if (! empty($filters['usuario'])) {
            $terminosUsuario = preg_split('/\s+/', trim($filters['usuario']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($terminosUsuario as $termino) {
                $query->where(function (Builder $search) use ($termino): void {
                    $like = '%'.$termino.'%';
                    $search->where('p.ci', 'like', $like)
                        ->orWhere('p.nombre', 'like', $like)
                        ->orWhere('p.primerapellido', 'like', $like)
                        ->orWhere('p.segundoapellido', 'like', $like)
                        ->orWhere('student_user.usuario', 'like', $like)
                        ->orWhere('p.email', 'like', $like)
                        ->orWhere('actor_person.nombre', 'like', $like)
                        ->orWhere('actor_person.primerapellido', 'like', $like)
                        ->orWhere('actor_person.segundoapellido', 'like', $like)
                        ->orWhere('actor_user.usuario', 'like', $like)
                        ->orWhere('actor_person.email', 'like', $like);
                });
            }
        }

        // La búsqueda general cubre personas, usuarios, depósitos, comprobantes,
        // identificadores de pago y códigos de inscripción/postulación/concepto.
        if (! empty($filters['buscar'])) {
            $terminos = preg_split('/\s+/', trim($filters['buscar']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($terminos as $termino) {
                $query->where(function (Builder $search) use ($termino): void {
                    $search->where('p.ci', 'like', '%'.$termino.'%')
                        ->orWhere('p.nombre', 'like', '%'.$termino.'%')
                        ->orWhere('p.primerapellido', 'like', '%'.$termino.'%')
                        ->orWhere('p.segundoapellido', 'like', '%'.$termino.'%')
                        ->orWhere('po.ci', 'like', '%'.$termino.'%')
                        ->orWhere('po.nombre', 'like', '%'.$termino.'%')
                        ->orWhere('po.primerapellido', 'like', '%'.$termino.'%')
                        ->orWhere('po.segundoapellido', 'like', '%'.$termino.'%')
                        ->orWhere('actor_person.nombre', 'like', '%'.$termino.'%')
                        ->orWhere('actor_person.primerapellido', 'like', '%'.$termino.'%')
                        ->orWhere('actor_person.email', 'like', '%'.$termino.'%')
                        ->orWhere('student_user.usuario', 'like', '%'.$termino.'%')
                        ->orWhere('actor_user.usuario', 'like', '%'.$termino.'%')
                        ->orWhere('cb.deposito', 'like', '%'.$termino.'%')
                        ->orWhere('i.deposito', 'like', '%'.$termino.'%')
                        ->orWhere('po.deposito', 'like', '%'.$termino.'%')
                        ->orWhere('cb.id_comprobante', 'like', '%'.$termino.'%')
                        ->orWhere('pago.id_pago', 'like', '%'.$termino.'%')
                        ->orWhere('i.codigo', 'like', '%'.$termino.'%')
                        ->orWhere('po.codigo', 'like', '%'.$termino.'%')
                        ->orWhere('detalle.codigo', 'like', '%'.$termino.'%');
                });
            }
        }

        // Las exportaciones usan el conjunto completo filtrado; la paginación
        // solo se aplica a la tabla de la pantalla.
        if (! empty($filters['formato'])) {
            $pagosData = (clone $query)
                ->orderByRaw('COALESCE(cb.fecha, i.fecha, po.fecha, pago.created_at) DESC')
                ->orderByDesc('pago.id_pago')
                ->get();

            if ($filters['formato'] === 'pdf') {
                return view('pagos.pago.pdf', compact('pagosData'));
            }

            $rows = $pagosData->map(fn ($pago): array => [
                $pago->inscrito_codigo ?? $pago->postulante_codigo ?? 'PAG-'.$pago->id_pago,
                $pago->deposito,
                $pago->fecha,
                trim(implode(' ', array_filter([
                    $pago->persona_nombre ?? $pago->postulante_nombre,
                    $pago->persona_primerapellido ?? $pago->postulante_primerapellido,
                    $pago->persona_segundoapellido ?? $pago->postulante_segundoapellido,
                ]))),
                $pago->concepto ?? '—',
                $pago->usuario_login ?? $pago->usuario_nombre,
                $pago->monto,
                $pago->valido === null ? 'Pendiente' : ($pago->valido ? 'Sí' : 'No'),
                $pago->estado_registro,
            ])->all();

            return $this->exportarExcel(
                'Pagos_'.now()->format('Y-m-d_H-i').'.xlsx',
                ['Código', 'Depósito', 'Fecha', 'Persona', 'Concepto', 'Usuario', 'Monto', 'Válido', 'Estado'],
                $rows
            );
        }

        // Mantener los filtros en los enlaces de páginas y limitar el tamaño
        // de página a las opciones validadas.
        $pagos = $query
            ->orderByRaw('COALESCE(cb.fecha, i.fecha, po.fecha, pago.created_at) DESC')
            ->orderByDesc('pago.id_pago')
            ->paginate((int) ($filters['por_pagina'] ?? 10))
            ->withQueryString();

        return view('pagos.pago.index', compact('pagos'));
    }

    /**
     * Arma la consulta de lectura de pagos y reúne la información relacionada
     * con sus detalles, inscripción o postulación, comprobante y personas.
     */
    private function pagosQuery(): Builder
    {
        // Consolidar los conceptos por pago para evitar repetir una fila por detalle.
        $detalles = DB::table('detalle')
            ->select('id_pago')
            ->selectRaw('MAX(codigo) as codigo')
            ->selectRaw('MAX(nombre) as concepto')
            ->selectRaw('SUM(monto) as monto')
            ->groupBy('id_pago');

        // Los LEFT JOIN conservan pagos aunque falte algún dato relacionado.
        // El comprobante se enlaza por inscripción cuando existe; de lo contrario,
        // por postulación. Los campos COALESCE priorizan el dato disponible.
        // El estado combina el estado del pago, la inscripción y el comprobante;
        // el monto consolidado de detalles prevalece sobre el monto del pago.
        return DB::table('pago')
            ->leftJoinSub($detalles, 'detalle', 'detalle.id_pago', '=', 'pago.id_pago')
            ->leftJoin('inscrito as i', 'i.id_inscrito', '=', 'pago.id_inscrito')
            ->leftJoin('postulante as po', 'po.id_postulante', '=', 'pago.id_postulante')
            ->leftJoin('comprobante as cb', function ($join): void {
                $join->where(function ($receipt): void {
                    $receipt->whereNotNull('pago.id_inscrito')
                        ->whereColumn('cb.id_inscrito', 'pago.id_inscrito');
                })->orWhere(function ($receipt): void {
                    $receipt->whereNull('pago.id_inscrito')
                        ->whereColumn('cb.id_postulante', 'pago.id_postulante');
                });
            })
            ->leftJoin('personas as p', function ($join): void {
                $join->on(
                    'p.id_persona',
                    '=',
                    DB::raw('COALESCE(cb.id_estudiante, i.id_persona)')
                );
            })
            ->leftJoin('users as student_user', 'student_user.id_persona', '=', 'p.id_persona')
            ->leftJoin('users as actor_user', 'actor_user.id_persona', '=', 'cb.id_usuario')
            ->leftJoin('personas as actor_person', 'actor_person.id_persona', '=', 'actor_user.id_persona')
            ->select(
                'pago.id_pago',
                DB::raw('COALESCE(cb.deposito, i.deposito, po.deposito) as deposito'),
                DB::raw('COALESCE(cb.fecha, i.fecha, po.fecha, pago.created_at) as fecha'),
                DB::raw('COALESCE(detalle.monto, pago.monto, 0) as monto'),
                'cb.valido',
                DB::raw("CASE WHEN pago.estado = 0 OR (i.id_inscrito IS NOT NULL AND i.activo = 0) OR (cb.id_comprobante IS NOT NULL AND cb.activo = 0) THEN 'Deshabilitado' WHEN i.id_inscrito_reemplazo IS NOT NULL THEN 'Reemplazado' ELSE 'Activo' END as estado_registro"),
                DB::raw('COALESCE(cb.id_gestionacademica, i.id_gestionacademica, po.id_gestionacademica) as id_gestionacademica'),
                'p.nombre as persona_nombre',
                'p.primerapellido as persona_primerapellido',
                'p.segundoapellido as persona_segundoapellido',
                'p.ci as persona_ci',
                'po.nombre as postulante_nombre',
                'po.primerapellido as postulante_primerapellido',
                'po.segundoapellido as postulante_segundoapellido',
                'po.ci as postulante_ci',
                DB::raw('COALESCE(student_user.usuario, actor_person.nombre) as usuario_nombre'),
                DB::raw('COALESCE(student_user.usuario, actor_user.usuario) as usuario_login'),
                DB::raw('COALESCE(p.email, actor_person.email) as usuario_email'),
                'i.codigo as inscrito_codigo',
                'po.codigo as postulante_codigo',
                'detalle.codigo as detalle_codigo',
                'detalle.concepto',
                'cb.id_comprobante'
            );
    }
}
