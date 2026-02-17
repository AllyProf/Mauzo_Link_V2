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
        // Check if column already exists
        if (!Schema::hasColumn('waiter_daily_reconciliations', 'reconciliation_type')) {
            // Add reconciliation_type column first (with default value)
            Schema::table('waiter_daily_reconciliations', function (Blueprint $table) {
                $table->enum('reconciliation_type', ['bar', 'food', 'combined'])->default('combined')->after('reconciliation_date');
            });
        }
        
        // Update existing records to 'combined' type (if not already set)
        DB::table('waiter_daily_reconciliations')
            ->whereNull('reconciliation_type')
            ->orWhere('reconciliation_type', '')
            ->update(['reconciliation_type' => 'combined']);
        
        // Add new unique constraint including reconciliation_type (if it doesn't exist)
        if (!$this->indexExists('waiter_daily_reconciliations', 'wd_waiter_date_type_unique')) {
            Schema::table('waiter_daily_reconciliations', function (Blueprint $table) {
                $table->unique(['waiter_id', 'reconciliation_date', 'reconciliation_type'], 'wd_waiter_date_type_unique');
            });
        }
    }
    
    /**
     * Check if an index exists
     */
    private function indexExists($table, $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($result) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiter_daily_reconciliations', function (Blueprint $table) {
            $table->dropUnique('wd_waiter_date_type_unique');
            $table->dropColumn('reconciliation_type');
        });
    }
};
