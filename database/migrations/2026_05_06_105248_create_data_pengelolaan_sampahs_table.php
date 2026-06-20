<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_pengelolaan_sampahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_sampah_id')->constrained('data_sampahs')->cascadeOnDelete();
            $table->foreignId('jenis_pengelolaan_id')->constrained('jenis_pengelolaans');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('jumlah', 8, 2); // dalam KG
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pengelolaan_sampahs');
    }
};
