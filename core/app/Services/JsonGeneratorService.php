<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class JsonGeneratorService
{
    private const ITEMS_PER_PAGE = 10;

    /**
     * Generate paginated JSON files for a given table.
     * Files land in: public/data/{table}/page{N}.json
     */
    public function generate(string $table): void
    {
        // JSON generation is deprecated in favor of API fetching.
        // We leave this empty so old calls to it don't break the application.
        return;
    }

    private function normalizeRow(array $row): array
    {
        return $row;
    }

    public function regenerateAll(): void
    {
        return;
    }
}
