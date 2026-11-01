<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

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
        $rawRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $headerIndex = null;
        $headers = [];

        foreach (array_slice($rawRows, 0, 10, true) as $index => $rawRow) {
            $candidate = array_map(fn ($value) => $this->header((string) $value), $rawRow);
            if (array_intersect($candidate, ['nombre', 'materia', 'asignatura']) !== []) {
                $headerIndex = $index;
                $headers = $candidate;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new RuntimeException('No se encontró la fila de encabezados del plan de estudios.');
        }

        return $this->mapRows(array_slice($rawRows, $headerIndex + 1), $headers, $headerIndex + 2);
    }

    private function pdfRows(UploadedFile $file): array
    {
        $text = (new PdfParser())->parseFile($file->getRealPath())->getText();
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), fn (string $line): bool => $line !== ''));
        $lines = $this->joinBrokenCodes($lines);
        $cursos = [];
        $rows = [];
        $nivelActual = null;

        foreach ($lines as $index => $line) {
            if ($this->looksLikeHeader($line)) {
                continue;
            }

            $nivelEncabezado = $this->pdfLevelLabel($line);
            if ($nivelEncabezado !== null) {
                $nivelActual = $nivelEncabezado;
            }

            $inlineRow = $this->inlinePdfRow($line, $nivelActual);
            if ($inlineRow !== null) {
                $rows[$index] = $inlineRow;
                continue;
            }

            if (
                isset($lines[$index - 1])
                && preg_match('/^\d+$/u', $lines[$index - 1])
                && preg_match('/^[A-Z0-9][A-Z0-9._-]*-\d{2,}[A-Z0-9._-]*$/iu', $line)
            ) {
                $cursos[] = [
                    'codigo' => $line,
                    'indice' => $index,
                    'indiceNumero' => $index - 1,
                    'numero' => $lines[$index - 1],
                    'nivel' => $nivelActual,
                ];
            }
        }

        foreach ($cursos as $index => $curso) {
            $inicio = $curso['indice'] + 1;
            $fin = $cursos[$index + 1]['indiceNumero'] ?? count($lines);
            $sufijo = trim(implode(' ', array_slice($lines, $inicio, $fin - $inicio)));

            if (! preg_match(
                '/^(?<nombre>.+?)\s+(?<horas>\d+(?:[.,]\d+)?)\s+(?<creditaje>\d+(?:[.,]\d+)?)\s+(?<curricular>sí|si|no|true|false|1|0)(?:\s+(?<prerequisitos>.*))?$/iu',
                $sufijo,
                $columns
            )) {
                throw new RuntimeException('No se pudo interpretar la materia "' . $curso['codigo'] . '" en el PDF.');
            }

            $rows[$curso['indice']] = $this->normalizedRow([
                'codigo' => $curso['codigo'],
                'nombre' => $columns['nombre'],
                'horas' => $columns['horas'],
                'nivel' => $curso['nivel'],
                'numero' => $curso['numero'],
                'curricular' => $columns['curricular'],
                'prerequisitos' => $columns['prerequisitos'] ?? '',
            ]);
        }

        ksort($rows);
        $rows = array_values($rows);
        if ($rows === []) {
            throw new RuntimeException('No se encontraron filas de materias en el PDF. Use una tabla con las columnas del archivo materias.xls.');
        }

        return $rows;
    }

    private function inlinePdfRow(string $line, ?int $nivelActual): ?array
    {
        if (! preg_match(
            '/^(?<prefix>.*?)\s+(?<codigo>[A-Z0-9][A-Z0-9._-]*-\d{2,}[A-Z0-9._-]*)\s+(?<sufijo>.+)$/iu',
            $line,
            $match
        )) {
            return null;
        }

        if (! preg_match(
            '/^(?<nombre>.+?)\s+(?<horas>\d+(?:[.,]\d+)?)\s+(?<creditaje>\d+(?:[.,]\d+)?)\s+(?<curricular>sí|si|no|true|false|1|0)(?:\s+(?<prerequisitos>.*))?$/iu',
            trim($match['sufijo']),
            $columns
        )) {
            return null;
        }

        $prefix = trim($match['prefix']);
        $numero = null;
        if (preg_match('/(?:^|\s)(\d+)\s*$/u', $prefix, $numberMatch)) {
            $numero = $numberMatch[1];
            $prefix = trim(substr($prefix, 0, -strlen($numberMatch[0])));
        }

        return $this->normalizedRow([
            'codigo' => $match['codigo'],
            'nombre' => $columns['nombre'],
            'horas' => $columns['horas'],
            'nivel' => $this->parseLevel($prefix) ?? $nivelActual,
            'numero' => $numero,
            'curricular' => $columns['curricular'],
            'prerequisitos' => $columns['prerequisitos'] ?? '',
        ]);
    }

    private function joinBrokenCodes(array $lines): array
    {
        $joined = [];
        for ($index = 0; $index < count($lines); $index++) {
            if (
                preg_match('/^[A-Z0-9][A-Z0-9._-]*-$/iu', $lines[$index])
                && isset($lines[$index + 1])
                && preg_match('/^\d{2,}[A-Z0-9._-]*$/iu', $lines[$index + 1])
            ) {
                $joined[] = $lines[$index] . $lines[++$index];
                continue;
            }

            $joined[] = $lines[$index];
        }

        return $joined;
    }

    private function pdfLevelLabel(string $line): ?int
    {
        $normalized = $this->header($line);
        $ordinales = 'primer|primero|segundo|tercer|tercero|cuarto|quinto|sexto|septimo|octavo|noveno|decimo';

        if (preg_match('/^(?:' . $ordinales . ')(?:ano|semestre|nivel)?$/u', $normalized)) {
            return $this->parseLevel($normalized);
        }

        if (preg_match('/^(?:ano|semestre|nivel)\d+$/u', $normalized)) {
            return $this->parseLevel($normalized);
        }

        return null;
    }

    private function mapRows(array $rawRows, array $headers, int $firstDataRow = 2): array
    {
        $aliases = [
            'codigo' => ['codigo', 'codigomateria', 'clave'],
            'nombre' => ['nombre', 'materia', 'asignatura', 'unidaddeaprendizaje'],
            'horas' => ['horas', 'horassemana', 'cargahoraria'],
            'nivel' => ['nivel', 'semestre', 'periodo'],
            'numero' => ['numero', 'nro', 'orden', 'n'],
            'curricular' => ['curricular', 'escurricular'],
            'prerequisitos' => ['prerequisitos', 'prerrequisitos', 'prerequisito', 'prerrequisito'],
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

        if (! isset($indexes['nombre'])) {
            throw new RuntimeException('El archivo debe incluir una columna llamada nombre, materia o asignatura.');
        }

        $rows = [];
        foreach ($rawRows as $index => $rawRow) {
            $name = trim((string) ($rawRow[$indexes['nombre']] ?? ''));
            if ($name === '') {
                continue;
            }

            $row = ['nombre' => $name];
            foreach (['codigo', 'horas', 'nivel', 'numero', 'curricular', 'prerequisitos'] as $field) {
                if (isset($indexes[$field])) {
                    $row[$field] = $this->value($rawRow[$indexes[$field]] ?? null);
                }
            }

            try {
                $rows[] = $this->normalizedRow($row);
            } catch (RuntimeException $exception) {
                throw new RuntimeException('Error en la fila ' . ($firstDataRow + $index) . ': ' . $exception->getMessage(), 0, $exception);
            }
        }

        if ($rows === []) {
            throw new RuntimeException('El archivo no contiene ninguna materia para importar.');
        }

        return $rows;
    }

    private function normalizedRow(array $row): array
    {
        $nivel = $row['nivel'] ?? null;
        if ($nivel !== null && $nivel !== '') {
            $nivel = $this->parseLevel((string) $nivel);
            if ($nivel === null) {
                throw new RuntimeException('El nivel debe ser numérico o tener un formato como PRIMER AÑO.');
            }
        }

        $curricular = $this->parseBoolean($row['curricular'] ?? null);
        $prerequisitos = $this->parsePrerequisites((string) ($row['prerequisitos'] ?? ''));

        return [
            'codigo' => $this->value($row['codigo'] ?? null),
            'nombre' => trim((string) ($row['nombre'] ?? '')),
            'horas' => $this->value($row['horas'] ?? null),
            'nivel' => $nivel,
            'numero' => $this->value($row['numero'] ?? null),
            'curricular' => $curricular,
            'prerequisitos' => $prerequisitos,
        ];
    }

    private function parseLevel(string $value): ?int
    {
        $value = $this->header($value);
        if (preg_match('/\d+/u', $value, $match)) {
            return (int) $match[0];
        }

        foreach ([
            'primer' => 1,
            'primero' => 1,
            'segundo' => 2,
            'tercer' => 3,
            'tercero' => 3,
            'cuarto' => 4,
            'quinto' => 5,
            'sexto' => 6,
            'septimo' => 7,
            'octavo' => 8,
            'noveno' => 9,
            'decimo' => 10,
        ] as $word => $number) {
            if (str_contains($value, $word)) {
                return $number;
            }
        }

        return null;
    }

    private function parseBoolean(mixed $value): bool
    {
        $value = $this->header((string) $value);
        if ($value === '') {
            return true;
        }
        if (in_array($value, ['si', 'true', '1', 'yes', 'curricular'], true)) {
            return true;
        }
        if (in_array($value, ['no', 'false', '0', 'nocurricular'], true)) {
            return false;
        }

        throw new RuntimeException('El campo curricular debe indicar Sí o No.');
    }

    private function parsePrerequisites(string $value): array
    {
        preg_match_all('/[A-Z0-9][A-Z0-9._-]*-\d{2,}[A-Z0-9._-]*/iu', $value, $matches);

        return array_values(array_unique(array_map(
            fn (string $code): string => strtoupper($code),
            $matches[0]
        )));
    }

    private function value(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : str_replace(',', '.', $value);
    }

    private function header(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'º' => '', '°' => '',
        ]);

        return preg_replace('/[^a-z0-9]/u', '', $value) ?? '';
    }

    private function looksLikeHeader(string $line): bool
    {
        $normalized = $this->header($line);

        return str_contains($normalized, 'codigo')
            && (str_contains($normalized, 'nombre') || str_contains($normalized, 'materia'))
            && (str_contains($normalized, 'nivel') || str_contains($normalized, 'horas'));
    }
}
