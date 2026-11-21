<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

class PlanEstudiosImporter
{
    /**
     * Selecciona el lector a partir de la extensión original del archivo y devuelve filas normalizadas.
     * PDF usa extracción y reconstrucción de texto; Excel/CSV detecta encabezados de columnas.
     * Formatos que no cumplan la estructura esperada producen RuntimeException descriptivo.
     */
    public function rows(UploadedFile $file): array
    {
        return strtolower($file->getClientOriginalExtension()) === 'pdf'
            ? $this->pdfRows($file)
            : $this->spreadsheetRows($file);
    }

    /**
     * Carga la hoja activa, busca una columna de nombre entre sus primeras diez filas y mapea el resto.
     * La fila detectada se usa para calcular números de fila comprensibles en mensajes de error.
     */
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

    /**
     * Extrae el texto del PDF y prepara sus líneas antes de reconocer materias en formatos tabulares o multilínea.
     * Mantiene el nivel del último encabezado encontrado, reconstruye nombre/horas/creditaje/estado,
     * y lanza un error si una materia o el documento completo no pueden interpretarse.
     */
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

    /**
     * Intenta interpretar una fila compacta que contiene código, nombre y columnas en la misma línea.
     * Devuelve null para que el lector pruebe el formato multilínea; si coincide, normaliza nivel y prerrequisitos.
     */
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

    /**
     * Repara códigos partidos por salto de línea cuando el primer fragmento termina en guion.
     * Consume ambos fragmentos como uno solo para que la detección posterior identifique el código completo.
     */
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

    /**
     * Normaliza un encabezado PDF y reconoce ordinales o etiquetas numéricas de año/semestre/nivel.
     * Devuelve null para líneas que no describen un nivel, lo que permite conservar el nivel actual.
     */
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

    /**
     * Asocia encabezados alternativos con campos canónicos y localiza el índice de cada columna.
     * Omite filas sin nombre, normaliza filas válidas y anota el número original si alguna falla;
     * exige al menos una fila con nombre de materia para aceptar el archivo.
     */
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

    /**
     * Convierte una fila leída desde Excel, CSV o PDF al contrato común del importador.
     * Normaliza valores opcionales, interpreta el nivel y estado curricular y extrae códigos de prerrequisitos.
     */
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

    /**
     * Convierte dígitos o palabras ordinales en el número de nivel correspondiente.
     * Primero busca una secuencia numérica; luego compara ordinales en español y devuelve null si no reconoce el texto.
     */
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

    /**
     * Convierte etiquetas aceptadas de sí/no, true/false o 1/0 al booleano curricular.
     * Un campo vacío se interpreta como curricular por compatibilidad; cualquier otro texto se rechaza.
     */
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

    /**
     * Extrae del texto los códigos con formato de materia, sin exigir un separador concreto entre ellos.
     * Convierte los códigos a mayúsculas y conserva una sola copia de cada código.
     */
    private function parsePrerequisites(string $value): array
    {
        preg_match_all('/[A-Z0-9][A-Z0-9._-]*-\d{2,}[A-Z0-9._-]*/iu', $value, $matches);

        return array_values(array_unique(array_map(
            fn (string $code): string => strtoupper($code),
            $matches[0]
        )));
    }

    /**
     * Limpia el valor de una celda y representa campos vacíos como null.
     * Cambia coma decimal por punto para que horas y creditajes puedan validarse numéricamente.
     */
    private function value(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : str_replace(',', '.', $value);
    }

    /**
     * Convierte encabezados a minúsculas sin diacríticos ni signos de puntuación.
     * El resultado compacto permite comparar alias aunque el archivo use espacios o abreviaturas.
     */
    private function header(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'º' => '', '°' => '',
        ]);

        return preg_replace('/[^a-z0-9]/u', '', $value) ?? '';
    }

    /**
     * Distingue el encabezado de una tabla PDF buscando código, nombre/materia y nivel/horas.
     * Las líneas que cumplen esos criterios se omiten como datos y no se interpretan como una asignatura.
     */
    private function looksLikeHeader(string $line): bool
    {
        $normalized = $this->header($line);

        return str_contains($normalized, 'codigo')
            && (str_contains($normalized, 'nombre') || str_contains($normalized, 'materia'))
            && (str_contains($normalized, 'nivel') || str_contains($normalized, 'horas'));
    }
}
