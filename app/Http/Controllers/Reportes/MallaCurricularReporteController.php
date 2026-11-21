<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Instituto;
use App\Models\Materia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MallaCurricularReporteController extends Controller
{
    /**
     * Muestra la malla de una carrera seleccionada y, cuando se solicita,
     * genera su versión PDF o Excel con materias agrupadas por nivel.
     */
    public function index(Request $request)
    {
        $formato = $request->input('formato');
        // Los enlaces de descarga también activan la generación y validación del reporte.
        $generado = $request->boolean('generar') || in_array($formato, ['pdf', 'excel'], true);

        // Se ofrecen todas las carreras para consulta; la selección se valida al generar.
        $carreras = Carrera::query()->orderBy('nombre')->orderBy('version')->get();
        $carrera = null;
        $niveles = collect();
        $totales = ['materias' => 0, 'horas' => 0, 'creditos' => 0];

        if ($generado) {
            $validated = $request->validate([
                'id_carrera' => ['required', 'integer', 'exists:carreras,id_carrera'],
            ], [
                'id_carrera.required' => 'Seleccione una carrera.',
                'id_carrera.exists' => 'La carrera seleccionada no existe.',
            ]);

            $carrera = Carrera::with('instituto')->findOrFail($validated['id_carrera']);
            // Carga prerrequisitos junto con las materias y mantiene un orden estable
            // para que la pantalla y los archivos descargados presenten el mismo plan.
            $materias = Materia::query()
                ->with('prerequisitos:id_materia,codigo,nombre')
                ->where('id_carrera', $carrera->id_carrera)
                ->orderBy('nivel')
                ->orderBy('numero')
                ->orderBy('nombre')
                ->get();

            // El nivel nulo se agrupa como cero, que la vista etiqueta como no asignado.
            $niveles = $materias->groupBy(fn (Materia $m) => $m->nivel ?? 0)->sortKeys();
            $totales = [
                'materias' => $materias->count(),
                'horas' => (int) $materias->sum('horas'),
                'creditos' => (int) $materias->sum('creditajemateria'),
            ];
        }

        // El PDF incorpora la identidad institucional y presenta la malla en vertical.
        if ($generado && $formato === 'pdf') {
            return Pdf::loadView('reportes.malla-curricular.pdf', [
                'carrera' => $carrera,
                'niveles' => $niveles,
                'totales' => $totales,
                'instituto' => Instituto::first(),
            ])->setPaper('a4', 'portrait')
                ->download($this->nombreArchivo($carrera, 'pdf'));
        }

        // Excel recibe las mismas materias agrupadas y los totales calculados arriba.
        if ($generado && $formato === 'excel') {
            return $this->exportarMallaExcel($carrera, $niveles, $totales);
        }

        return view('reportes.malla-curricular.index', compact(
            'carreras', 'generado', 'carrera', 'niveles', 'totales'
        ));
    }

    /**
     * Construye un nombre reconocible a partir del código de carrera y la fecha,
     * sustituyendo caracteres no apropiados para nombres de archivo.
     */
    private function nombreArchivo(Carrera $carrera, string $extension): string
    {
        return 'Malla_Curricular_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $carrera->codigo)
            .'_'.now()->format('Y-m-d_H-i').'.'.$extension;
    }

    /**
     * Prepara y transmite el libro Excel con materias, datos de plan,
     * prerrequisitos y sumatorias de horas y créditos.
     */
    private function exportarMallaExcel(Carrera $carrera, $niveles, array $totales)
    {
        $hoja = new Spreadsheet();
        $sheet = $hoja->getActiveSheet();
        $sheet->setTitle('Malla curricular');
        $sheet->setCellValue('A1', 'MALLA CURRICULAR - '.mb_strtoupper($carrera->nombre).' ('.$carrera->codigo.')');
        $sheet->setCellValue('A2', 'Nivel académico: '.($carrera->nivelacademico ?? '-').'   Versión: '.($carrera->version ?? '-'));
        $sheet->fromArray(['Nivel', 'N°', 'Código', 'Materia', 'Horas', 'Créditos', 'Prerrequisitos'], null, 'A4');
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()->setFillType('solid')->getStartColor()->setRGB('DCFCE7');

        // Cada materia ocupa una fila; los prerrequisitos se presentan como una
        // lista de códigos y al final se escriben los acumulados de la malla.
        $fila = 5;
        foreach ($niveles as $nivel => $materias) {
            foreach ($materias as $materia) {
                $sheet->fromArray([
                    $nivel ?: '-',
                    $materia->numero,
                    $materia->codigo,
                    $materia->nombre,
                    $materia->horas,
                    $materia->creditajemateria,
                    $materia->prerequisitos->pluck('codigo')->filter()->implode(', '),
                ], null, 'A'.$fila++);
            }
        }
        $sheet->setCellValue('D'.$fila, 'TOTAL');
        $sheet->setCellValue('E'.$fila, $totales['horas']);
        $sheet->setCellValue('F'.$fila, $totales['creditos']);
        $sheet->getStyle('D'.$fila.':F'.$fila)->getFont()->setBold(true);
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($hoja): void {
            (new Xlsx($hoja))->save('php://output');
        }, $this->nombreArchivo($carrera, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
