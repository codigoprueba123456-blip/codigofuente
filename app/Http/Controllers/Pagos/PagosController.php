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
            $query->whereDate('cb.fecha', '>=', $filters['desde']);
        }

        if (! empty($filters['hasta'])) {
            $query->whereDate('cb.fecha', '<=', $filters['hasta']);
        }

        if (! empty($filters['usuario'])) {
            $usuario = trim($filters['usuario']);
            $query->where(function (Builder $search) use ($usuario): void {
                $search->where('up.nombre', 'like', '%'.$usuario.'%')
                    ->orWhere('up.primerapellido', 'like', '%'.$usuario.'%')
                    ->orWhere('up.email', 'like', '%'.$usuario.'%')
                    ->orWhere('u.usuario', 'like', '%'.$usuario.'%');
            });
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
                        ->orWhere('up.nombre', 'like', '%'.$termino.'%')
                        ->orWhere('up.primerapellido', 'like', '%'.$termino.'%')
                        ->orWhere('up.email', 'like', '%'.$termino.'%')
                        ->orWhere('u.usuario', 'like', '%'.$termino.'%')
                        ->orWhere('cb.deposito', 'like', '%'.$termino.'%')
                        ->orWhere('cb.id_comprobante', 'like', '%'.$termino.'%')
                        ->orWhere('i.codigo', 'like', '%'.$termino.'%')
                        ->orWhere('po.codigo', 'like', '%'.$termino.'%');
                });
            }
        }

        if (! empty($filters['formato'])) {
            $pagosData = (clone $query)
                ->orderByDesc('cb.fecha')
                ->orderByDesc('cb.id_comprobante')
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
                        $pago->usuario_nombre,
                        $pago->monto,
                        $pago->valido ? 'Sí' : 'No',
                    ])->all();

            return $this->exportarExcel(
                'Pagos_'.now()->format('Y-m-d_H-i').'.xlsx',
                ['Código', 'Depósito', 'Fecha', 'Persona', 'Usuario', 'Monto', 'Válido'],
                $rows
            );
        }

        $pagos = $query
            ->orderByDesc('cb.fecha')
            ->orderByDesc('cb.id_comprobante')
            ->paginate((int) ($filters['por_pagina'] ?? 10))
            ->withQueryString();

        return view('pagos.pago.index', compact('pagos'));
    }

    private function pagosQuery(): Builder
    {
        $detalles = DB::table('detalle')
            ->select('id_pago')
            ->selectRaw('SUM(monto) as monto')
            ->groupBy('id_pago');
        $pagos = DB::table('pago as pago')
            ->leftJoinSub($detalles, 'detalle', 'detalle.id_pago', '=', 'pago.id_pago')
            ->select('pago.id_inscrito', 'pago.id_postulante')
            ->selectRaw('SUM(COALESCE(detalle.monto, pago.monto, 0)) as monto')
            ->groupBy('pago.id_inscrito', 'pago.id_postulante');

        return DB::table('comprobante as cb')
            ->leftJoin('inscrito as i', 'i.id_inscrito', '=', 'cb.id_inscrito')
            ->leftJoin('postulante as po', 'po.id_postulante', '=', 'cb.id_postulante')
            ->leftJoinSub($pagos, 'pa', function ($join): void {
                $join->on('pa.id_inscrito', '=', 'cb.id_inscrito')
                    ->orOn('pa.id_postulante', '=', 'cb.id_postulante');
            })
            ->leftJoin('personas as p', function ($join): void {
                $join->on('p.id_persona', '=', 'cb.id_estudiante')
                    ->orOn('p.id_persona', '=', 'i.id_persona');
            })
            ->leftJoin('users as u', 'u.id_persona', '=', 'cb.id_usuario')
            ->leftJoin('personas as up', 'up.id_persona', '=', 'u.id_persona')
            ->select(
                'cb.id_comprobante as id_pago',
                'cb.deposito',
                'cb.fecha',
                DB::raw('COALESCE(pa.monto, 0) as monto'),
                'cb.valido',
                'cb.id_gestionacademica',
                'p.nombre as persona_nombre',
                'p.primerapellido as persona_primerapellido',
                'p.segundoapellido as persona_segundoapellido',
                'p.ci as persona_ci',
                'po.nombre as postulante_nombre',
                'po.primerapellido as postulante_primerapellido',
                'po.segundoapellido as postulante_segundoapellido',
                'po.ci as postulante_ci',
                'up.nombre as usuario_nombre',
                'up.email as usuario_email',
                'i.codigo as inscrito_codigo',
                'po.codigo as postulante_codigo'
            )
            ->addSelect(DB::raw('u.usuario as usuario_login'));
    }
}
