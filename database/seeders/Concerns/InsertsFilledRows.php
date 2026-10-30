<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait InsertsFilledRows
{
    protected function insertFilledRows(
        string $table,
        array $rows,
        array $hashedFields = [],
        bool $timestamps = true
    ): void {
        foreach ($rows as $row) {
            if (collect($row)->every(static fn ($value): bool => $value === null || $value === '')) {
                continue;
            }

            foreach ($hashedFields as $field) {
                if (!empty($row[$field])) {
                    $row[$field] = Hash::make($row[$field]);
                }
            }

            if ($timestamps) {
                $now = now();
                $row += [
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table($table)->insert($row);
        }
    }
}
