// database/migrations/xxxx_xx_xx_remove_role_column_from_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus kolom 'role' lama karena sudah digantikan oleh Spatie.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Kembalikan kolom 'role' jika migration di-rollback.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['Koordinator', 'fasilitator'])
                  ->default('fasilitator')
                  ->after('password');
        });
    }
};