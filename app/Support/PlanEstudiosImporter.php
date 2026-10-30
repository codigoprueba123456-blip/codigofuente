<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use RuntimeException;

class PlanEstudiosImporter
{
    public function rows(UploadedFile $file): array
    {
        return strtolower($file->getClientOriginalExtension()) === 'pdf'
            ? $this->pdfRows($file)
            : $this->spreadsheetRows($file);
    }

    private function spreadsheetRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);

        if (count($rawRows) < 2) {
            throw new RuntimeException('El archivo no contiene una fila de encabezados y al menos una materia.');
        }

        $headers = array_map(fn ($value) => $this->header((string) $value), array_shift($rawRows));

        return $this->mapRows($rawRows, $headers);
    }

    private function pdfRows(UploadedFile $file): array
    {
        $text = (new PdfParser())->parseFile($file->getRealPath())->getText();
        $lines = preg_split('/\R/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if ($line === '' || $this->looksLikeHeader($line)) {
                continue;
            }

            $columns = preg_split('/\s{2,}|\||;|\t/u', $line, -1, PREG_SPLIT_NO_EMPTY);
            if (count($columns) < 2) {
                continue;
            }

            $rows[] = $this->pdfRow($columns);
        }

        if ($rows === []) {
            throw new RuntimeException('No se encontraron filas de materias en el PDF. Use una tabla con código, nombre y datos numéricos.');
        }

        return $rows;
    }

    private function pdfRow(array $columns): array
    {
        $code = null;
        if (preg_match('/^[A-ZÁÉÍÓÚÑ0-9][A-ZÁÉÍÓÚÑ0-9._-]*$/iu', $columns[0]) && preg_match('/\d/u', $columns[0])) {
            $code = $columns[0];
            array_shift($columns);
        }

        $numeric = [];
        while ($columns !== [] && count($numeric) < 4 && preg_match('/^\d+(?:[.,]\d+)?$/', end($columns))) {
            array_unshift($numeric, array_pop($columns));
        }

        return [
            'codigo' => $code,
            'nombre' => trim(implode(' ', $columns)),
            'creditajemateria' => $numeric[0] ?? null,
            'horas' => $numeric[1] ?? null,
            'nivel' => $numeric[2] ?? null,
            'numero' => $numeric[3] ?? null,
        ];
    }

    private function mapRows(array $rawRows, array $headers): array
    {
        $aliases = [
            'codigo' => ['codigo', 'codigomateria', 'clave'],
            'nombre' => ['nombre', 'materia', 'asignatura', 'unidaddeaprendizaje'],
            'creditajemateria' => ['creditos', 'credito', 'creditajemateria', 'creditaje'],
            'horas' => ['horas', 'horassemana', 'cargahoraria'],
            'nivel' => ['nivel', 'semestre', 'periodo'],
            'numero' => ['numero', 'orden', 'nro'],
        ];
        $indexes = [];

        foreach ($aliases as $field => $names) {
            foreach ($names as $name) {
                $index = array_search($name, $headers, true);
                if ($index !== false) {
                    $indexes[$field] = $index;
                    break;
                }
            }
        }

        if (!isset($indexes['nombre'])) {
            throw new RuntimeException('El archivo debe incluir una columna llamada nombre, materia o asignatura.');
        }

        $rows = [];
        foreach ($rawRows as $rawRow) {
            $name = trim((string) ($rawRow[$indexes['nombre']] ?? ''));
            if ($name === '') {
                continue;
            }

            $row = ['nombre' => $name];
            foreach (array_keys($aliases) as $field) {
                if ($field !== 'nombre' && isset($indexes[$field])) {
                    $row[$field] = $this->value($rawRow[$indexes[$field]] ?? null);
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function header(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', ' ' => '', '_' => '', '-' => '']);
    }

    private function value(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : str_replace(',', '.', $value);
    }

    private function looksLikeHeader(string $line): bool
    {
        $normalized = $this->header($line);
        return str_contains($normalized, 'nombre') || str_contains($normalized, 'asignatura') || str_contains($normalized, 'materia');
    }
}