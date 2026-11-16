<?php

namespace Database\Seeders\Modulos;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VincularDocumentosIdentidadSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Este seeder de documentos temporales no se puede ejecutar en producción.');
        }

        $documentos = [
            'frontal' => [
                'origen' => base_path('database/seeders/img_documentos/carnet1.jpeg'),
                'destino' => 'demo/inscripciones/documentos/carnet1.jpeg',
            ],
            'reverso' => [
                'origen' => base_path('database/seeders/img_documentos/carnet2.jpeg'),
                'destino' => 'demo/inscripciones/documentos/carnet2.jpeg',
            ],
            'nacimiento' => [
                'origen' => base_path('database/seeders/img_documentos/certificado_de_nacimiento.webp'),
                'destino' => 'demo/inscripciones/documentos/certificado_de_nacimiento.webp',
            ],
            'diploma' => [
                'origen' => base_path('database/seeders/img_documentos/diploma.webp'),
                'destino' => 'demo/inscripciones/documentos/diploma.webp',
            ],
        ];

        foreach ($documentos as $documento) {
            if (! is_file($documento['origen']) || ! is_readable($documento['origen'])) {
                throw new RuntimeException("No se encontró o no se puede leer el archivo {$documento['origen']}.");
            }
        }

        $disco = Storage::disk('public');
        $rutas = [];

        foreach ($documentos as $nombre => $documento) {
            $contenido = file_get_contents($documento['origen']);
            if ($contenido === false || ! $disco->put($documento['destino'], $contenido)) {
                throw new RuntimeException("No se pudo copiar el documento de identidad {$nombre} al almacenamiento público.");
            }

            $rutas[$nombre] = $documento['destino'];
        }

        $this->asegurarEnlacePublico();

        $actualizados = DB::transaction(function () use ($disco, $rutas): array {
            return [
                'estudiantes' => $this->completarDocumentos(
                    $disco,
                    'estudiante',
                    'id_persona',
                    [
                        'path_ci' => $rutas['frontal'],
                        'path_ci_reverso' => $rutas['reverso'],
                        'path_nacimiento' => $rutas['nacimiento'],
                        'path_diploma' => $rutas['diploma'],
                    ]
                ),
                'postulantes' => $this->completarDocumentos(
                    $disco,
                    'postulante',
                    'id_postulante',
                    [
                        'path_ci_frontal' => $rutas['frontal'],
                        'path_ci_reverso' => $rutas['reverso'],
                        'path_nacimiento' => $rutas['nacimiento'],
                        'path_diploma' => $rutas['diploma'],
                    ]
                ),
            ];
        });

        $this->command?->info(
            "Documentos temporales vinculados: {$actualizados['estudiantes']} estudiantes y {$actualizados['postulantes']} postulantes."
        );
    }

    private function asegurarEnlacePublico(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $enlace = public_path('storage');
        $destino = storage_path('app/public');

        if (file_exists($enlace) || is_link($enlace)) {
            if ($this->normalizarRuta(realpath($enlace)) !== $this->normalizarRuta(realpath($destino))) {
                throw new RuntimeException("La ruta {$enlace} ya existe, pero no apunta a {$destino}.");
            }

            return;
        }

        File::link($destino, $enlace);

        if ($this->normalizarRuta(realpath($enlace)) !== $this->normalizarRuta(realpath($destino))) {
            throw new RuntimeException("No se pudo crear el enlace público {$enlace}; revisa los permisos de archivos.");
        }
    }

    private function normalizarRuta(string|false $ruta): string
    {
        return $ruta === false ? '' : strtolower(rtrim(str_replace('/', '\\', $ruta), '\\'));
    }

    private function completarDocumentos(
        FilesystemAdapter $disco,
        string $tabla,
        string $llave,
        array $documentos
    ): int {
        $actualizados = 0;
        $columnas = array_merge([$llave], array_keys($documentos));

        foreach (DB::table($tabla)->get($columnas) as $registro) {
            $cambios = [];

            foreach ($documentos as $columna => $rutaTemporal) {
                $rutaActual = trim((string) $registro->{$columna});
                if ($rutaActual === '' || ! $disco->exists($rutaActual)) {
                    $cambios[$columna] = $rutaTemporal;
                }
            }

            if ($cambios === []) {
                continue;
            }

            DB::table($tabla)
                ->where($llave, $registro->{$llave})
                ->update(array_merge($cambios, ['updated_at' => now()]));
            $actualizados++;
        }

        return $actualizados;
    }
}
