<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function montoInscripcion(int $idCarrera): float
    {
        $monto = DB::table('carreras as c')
            ->join('instituto as i', 'i.id_instituto', '=', 'c.id_instituto')
            ->where('c.id_carrera', $idCarrera)
            ->value(DB::raw('COALESCE(c.creditajematricula, 0) * COALESCE(i.preciocredito, 0)'));

        return (float) ($monto ?? 0);
    }

    protected function exportarExcel(string $filename, array $headings, array $rows)
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($headings, null, 'A1');
            if ($rows !== []) {
                $sheet->fromArray($rows, null, 'A2');
            }

            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
