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
        Schema::create('perpustakaan', function (Blueprint $table) {
            $table->string('buku_id', 64)->primary();
            $table->string('judul_buku', 200);
            $table->string('penulis', 160);
            $table->string('publisher_id', 64)->nullable();
            $table->integer('tahun')->nullable();
            $table->string('kategori', 40);
            $table->string('nama_file');
            $table->string('file_dokumen_key');
            $table->integer('ukuran_file');

            $table->foreign('publisher_id')->references('user_id')->on('pengguna')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perpustakaan');
    }
};
