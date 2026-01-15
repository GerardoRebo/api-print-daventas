<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class GenerateDatabaseSchema extends Command
{
    protected $signature = 'db:schema-export {--output=.github/database-schema.md}';
    protected $description = 'Genera/actualiza archivo de referencia de estructura de base de datos';

    public function handle()
    {
        $this->info('Generando esquema de base de datos...');

        // Obtener todas las tablas de information_schema
        $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME", [
            DB::getDatabaseName(),
        ]);

        $tableNames = collect($tables)->pluck('TABLE_NAME')->toArray();

        // Excluir tablas del sistema
        $excludedTables = ['migrations', 'password_resets', 'password_reset_tokens', 'personal_access_tokens'];
        $tableNames = array_diff($tableNames, $excludedTables);

        // Construir el contenido del markdown
        $markdown = "# Database Schema Reference\n\n";
        $markdown .= "Generado automáticamente: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $markdown .= "## Índice\n\n";

        // Crear tabla de contenidos
        foreach ($tableNames as $table) {
            $markdown .= "- [" . ucfirst($table) . "](#" . strtolower($table) . ")\n";
        }

        $markdown .= "\n---\n\n";

        // Generar documentación para cada tabla
        foreach ($tableNames as $table) {
            $markdown .= $this->generateTableDocumentation($table);
        }

        // Escribir el archivo
        $outputPath = $this->option('output');
        file_put_contents(base_path($outputPath), $markdown);

        $this->info("✓ Archivo generado: {$outputPath}");
        return Command::SUCCESS;
    }

    private function generateTableDocumentation($tableName)
    {
        $documentation = "## " . ucfirst($tableName) . "\n\n";

        // Obtener columnas
        $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA 
            FROM information_schema.COLUMNS 
            WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ? 
            ORDER BY ORDINAL_POSITION", [
            $tableName,
            DB::getDatabaseName(),
        ]);

        $documentation .= "| Columna | Tipo | Nullable | Default |\n";
        $documentation .= "|---------|------|----------|----------|\n";

        foreach ($columns as $column) {
            $name = $column->COLUMN_NAME;
            $type = $this->formatType($column->COLUMN_TYPE);
            $nullable = $column->IS_NULLABLE === 'YES' ? 'Sí' : 'No';
            $default = $column->COLUMN_DEFAULT !== null ? $this->formatDefault($column->COLUMN_DEFAULT) : '-';
            if ($column->EXTRA === 'auto_increment') {
                $default = 'auto_increment';
            }

            $documentation .= "| `$name` | $type | $nullable | $default |\n";
        }

        $documentation .= "\n";

        // Obtener indexes
        $indexes = DB::select("SELECT INDEX_NAME, COLUMN_NAME FROM information_schema.STATISTICS 
            WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ? 
            ORDER BY INDEX_NAME, SEQ_IN_INDEX", [
            $tableName,
            DB::getDatabaseName(),
        ]);

        if (!empty($indexes)) {
            $documentation .= "### Índices\n\n";
            $groupedIndexes = [];
            foreach ($indexes as $index) {
                $indexName = $index->INDEX_NAME;
                if (!isset($groupedIndexes[$indexName])) {
                    $groupedIndexes[$indexName] = [];
                }
                $groupedIndexes[$indexName][] = $index->COLUMN_NAME;
            }

            foreach ($groupedIndexes as $indexName => $cols) {
                $colList = implode(', ', $cols);
                $documentation .= "- **$indexName**: `$colList`\n";
            }
            $documentation .= "\n";
        }

        // Obtener foreign keys
        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [
            $tableName,
            DB::getDatabaseName(),
        ]);

        if (!empty($foreignKeys)) {
            $documentation .= "### Foreign Keys\n\n";
            foreach ($foreignKeys as $fk) {
                $documentation .= "- `" . $fk->COLUMN_NAME . "` → `" . $fk->REFERENCED_TABLE_NAME . "." . $fk->REFERENCED_COLUMN_NAME . "`\n";
            }
            $documentation .= "\n";
        }

        $documentation .= "---\n\n";

        return $documentation;
    }

    private function formatType($type)
    {
        // Retornar el tipo completo para mayor claridad
        return $type;
    }

    private function formatDefault($default)
    {
        if ($default === null) {
            return '-';
        }
        if ($default === '') {
            return "''";
        }
        if (strtoupper($default) === 'CURRENT_TIMESTAMP') {
            return 'CURRENT_TIMESTAMP';
        }

        return "`$default`";
    }
}
