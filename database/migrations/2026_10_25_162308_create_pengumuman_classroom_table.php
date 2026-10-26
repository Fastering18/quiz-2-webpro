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
        Schema::create('pengumuman_classroom', function (Blueprint $table) {
            $table->id('pengumuman_id');
            $table->string('kelas_id', 64);
            $table->string('konten', 2000)->nullable();
            $table->date('tanggal_unggah');

            $table->foreign('kelas_id')->references('kelas_id')->on('classroom')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman_classroom');
    }
};
