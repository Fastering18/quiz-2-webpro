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
        Schema::create('materi_kelas', function (Blueprint $table) {
            $table->id('materi_id');
            $table->foreignId('bagian_id')->constrained('bagian_kelas', 'bagian_id')->cascadeOnDelete();
            $table->string('judul_materi', 160);
            $table->string('nama_file');
            $table->string('file_materi_key');
            $table->integer('ukuran_file');
            $table->date('tanggal_unggah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materi_kelas');
    }
};
