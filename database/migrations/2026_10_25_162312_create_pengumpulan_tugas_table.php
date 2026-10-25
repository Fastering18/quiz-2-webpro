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
        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id('submit_id');
            $table->foreignId('tugas_id')->constrained('tugas_kelas', 'tugas_id')->cascadeOnDelete();
            $table->string('nrp', 14);
            $table->string('nama_file');
            $table->string('file_jawaban_key');
            $table->integer('ukuran_file');
            $table->string('komentar', 1000)->nullable();
            $table->dateTime('tanggal_kirim');
            $table->integer('nilai_tugas')->nullable();

            $table->foreign('nrp')->references('nrp')->on('mahasiswa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumpulan_tugas');
    }
};
