<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get existing columns
        $columns = $this->getTableColumns('student_registrations');

        Schema::table('student_registrations', function (Blueprint $table) use ($columns) {
            // Add columns only if they don't exist
            if (!in_array('age', $columns)) {
                $table->unsignedTinyInteger('age')->nullable()->after('phone');
            }

            if (!in_array('date_of_birth', $columns)) {
                $table->date('date_of_birth')->nullable()->after(in_array('age', $columns) ? 'age' : 'phone');
            }

            if (!in_array('school_name', $columns)) {
                $table->string('school_name', 200)->nullable()->after('date_of_birth');
            }

            if (!in_array('parent_name', $columns)) {
                $table->string('parent_name', 200)->nullable()->after('school_name');
            }

            if (!in_array('parent_phone', $columns)) {
                $table->string('parent_phone', 20)->nullable()->after('parent_name');
            }

            if (!in_array('parent_email', $columns)) {
                $table->string('parent_email')->nullable()->after('parent_phone');
            }
        });

        // Add indexes using raw SQL to avoid issues
        $this->addIndexIfNotExists('student_registrations', 'age', 'idx_age');
        $this->addIndexIfNotExists('student_registrations', 'date_of_birth', 'idx_dob');
        $this->addIndexIfNotExists('student_registrations', 'school_name', 'idx_school_name');
        $this->addIndexIfNotExists('student_registrations', 'parent_name', 'idx_parent_name');
        $this->addIndexIfNotExists('student_registrations', 'parent_phone', 'idx_parent_phone');
        $this->addIndexIfNotExists('student_registrations', 'parent_email', 'idx_parent_email');

        // Composite indexes
        $this->addCompositeIndexIfNotExists('student_registrations', ['age', 'school_name'], 'idx_age_school');
        $this->addCompositeIndexIfNotExists('student_registrations', ['parent_email', 'parent_phone'], 'idx_parent_contact');
        $this->addCompositeIndexIfNotExists('student_registrations', ['school_name', 'is_active'], 'idx_school_active');
        $this->addCompositeIndexIfNotExists('student_registrations', ['parent_email', 'is_active'], 'idx_parent_email_active');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first
        $this->dropIndexIfExists('student_registrations', 'idx_age');
        $this->dropIndexIfExists('student_registrations', 'idx_dob');
        $this->dropIndexIfExists('student_registrations', 'idx_school_name');
        $this->dropIndexIfExists('student_registrations', 'idx_parent_name');
        $this->dropIndexIfExists('student_registrations', 'idx_parent_phone');
        $this->dropIndexIfExists('student_registrations', 'idx_parent_email');
        $this->dropIndexIfExists('student_registrations', 'idx_age_school');
        $this->dropIndexIfExists('student_registrations', 'idx_parent_contact');
        $this->dropIndexIfExists('student_registrations', 'idx_school_active');
        $this->dropIndexIfExists('student_registrations', 'idx_parent_email_active');

        Schema::table('student_registrations', function (Blueprint $table) {
            $columns = $this->getTableColumns('student_registrations');

            if (in_array('parent_email', $columns)) {
                $table->dropColumn('parent_email');
            }
            if (in_array('parent_phone', $columns)) {
                $table->dropColumn('parent_phone');
            }
            if (in_array('parent_name', $columns)) {
                $table->dropColumn('parent_name');
            }
            if (in_array('school_name', $columns)) {
                $table->dropColumn('school_name');
            }
            if (in_array('date_of_birth', $columns)) {
                $table->dropColumn('date_of_birth');
            }
            if (in_array('age', $columns)) {
                $table->dropColumn('age');
            }
        });
    }

    /**
     * Get table columns using raw SQL
     */
    private function getTableColumns(string $tableName): array
    {
        $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
        return array_map(fn($column) => $column->Field, $columns);
    }

    /**
     * Add index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, string $column, string $indexName): void
    {
        $indexes = $this->getTableIndexes($table);

        if (!in_array($indexName, $indexes)) {
            try {
                DB::statement("CREATE INDEX {$indexName} ON {$table} ({$column})");
            } catch (\Exception $e) {
                // Index might already exist with different name, ignore
            }
        }
    }

    /**
     * Add composite index if it doesn't exist
     */
    private function addCompositeIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        $indexes = $this->getTableIndexes($table);

        if (!in_array($indexName, $indexes)) {
            try {
                $columnList = implode(', ', $columns);
                DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnList})");
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }
        }
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexes = $this->getTableIndexes($table);

        if (in_array($indexName, $indexes)) {
            try {
                DB::statement("DROP INDEX {$indexName} ON {$table}");
            } catch (\Exception $e) {
                // Index might not exist, ignore
            }
        }
    }

    /**
     * Get table indexes using raw SQL
     */
    private function getTableIndexes(string $tableName): array
    {
        $indexes = DB::select("SHOW INDEX FROM {$tableName}");
        return array_unique(array_map(fn($index) => $index->Key_name, $indexes));
    }
};
