<?php

namespace Database\Seeders\Modulos;

use Database\Seeders\PostulantesDemoSeeder;
use Illuminate\Database\Seeder;

class InscripcionesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PostulantesDemoSeeder::class);
    }
}
