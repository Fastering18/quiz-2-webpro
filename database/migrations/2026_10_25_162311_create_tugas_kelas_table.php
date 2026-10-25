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
        Schema::create('tugas_kelas', function (Blueprint $table) {
            $table->id('tugas_id');
            $table->foreignId('bagian_id')->constrained('bagian_kelas', 'bagian_id')->cascadeOnDelete();
            $table->integer('urutan');
            $table->string('judul_tugas', 160);
            $table->string('deskripsi_tugas', 2000)->nullable();
            $table->string('format_file', 50)->nullable();
            $table->dateTime('tenggat_waktu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugas_kelas');
    }
};
