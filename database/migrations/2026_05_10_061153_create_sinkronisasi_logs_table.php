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
        Schema::create('sinkronisasi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_sampah_id')->constrained('data_sampahs')->cascadeOnDelete();
            $table->enum('status', ['success', 'failed'])->default('failed');
            $table->integer('http_code')->nullable();
            $table->text('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinkronisasi_logs');
    }
};
