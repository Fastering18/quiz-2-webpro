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
        Schema::create('laporan_admin', function (Blueprint $table) {
            $table->string('tiket_id', 64)->primary();
            $table->string('user_id', 64);
            $table->string('kategori_modul', 40);
            $table->string('judul_laporan', 150);
            $table->string('deskripsi_kendala', 2000);
            $table->string('lampiran_nama_file')->nullable();
            $table->string('lampiran_bukti_key')->nullable();
            $table->string('status_tiket', 20);
            $table->dateTime('tanggal_lapor');
            $table->string('admin_id', 64)->nullable();
            $table->string('tanggapan_admin')->nullable();
            $table->dateTime('tanggal_tanggapan')->nullable();

            $table->foreign('user_id')->references('user_id')->on('pengguna');
            $table->foreign('admin_id')->references('user_id')->on('pengguna')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_admin');
    }
};
