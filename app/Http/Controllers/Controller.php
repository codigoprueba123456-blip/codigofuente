<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Resuelve el monto para una carrera, gestión y nivel mediante el servicio común de tarifas.
     */
    protected function montoInscripcion(int $idCarrera, int $idGestion, int $nivel): float
    {
        return app(\App\Support\MatriculaAmountService::class)
            ->calcular($idCarrera, $idGestion, $nivel);
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
