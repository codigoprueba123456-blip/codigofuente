<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagosController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'usuario' => ['nullable', 'string', 'max:100'],
            'buscar' => ['nullable', 'string', 'max:100'],
            'por_pagina' => ['nullable', 'integer', 'in:10,25,50'],
            'formato' => ['nullable', 'in:excel,pdf'],
        ]);

        $query = $this->pagosQuery();

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
                $pago->usuario_login ?? $pago->usuario_nombre,
                $pago->monto,
                $pago->valido === null ? 'Pendiente' : ($pago->valido ? 'Sí' : 'No'),
            ])->all();

            return $this->exportarExcel(
                'Pagos_'.now()->format('Y-m-d_H-i').'.xlsx',
                ['Código', 'Depósito', 'Fecha', 'Persona', 'Usuario', 'Monto', 'Válido'],
                $rows
            );
        }

        $pagos = $query
            ->orderByRaw('COALESCE(cb.fecha, i.fecha, po.fecha, pago.created_at) DESC')
            ->orderByDesc('pago.id_pago')
            ->paginate((int) ($filters['por_pagina'] ?? 10))
            ->withQueryString();

        return view('pagos.pago.index', compact('pagos'));
    }

    private function pagosQuery(): Builder
    {
        $detalles = DB::table('detalle')
            ->select('id_pago')
            ->selectRaw('MAX(codigo) as codigo')
            ->selectRaw('SUM(monto) as monto')
            ->groupBy('id_pago');

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
                'cb.id_comprobante'
            );
    }
}
