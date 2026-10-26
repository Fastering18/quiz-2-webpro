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
        Schema::create('classroom', function (Blueprint $table) {
            $table->string('kelas_id', 64)->primary();
            $table->string('kode_mk', 20);
            $table->string('nip', 32);
            $table->string('nama_kelas', 60);
            $table->string('semester_tahun', 12);
            $table->integer('kuota_kelas');

            $table->foreign('kode_mk')->references('kode_mk')->on('mata_kuliah');
            $table->foreign('nip')->references('nip')->on('dosen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom');
    }
};
