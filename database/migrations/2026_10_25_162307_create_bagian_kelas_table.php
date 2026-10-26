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
        Schema::create('bagian_kelas', function (Blueprint $table) {
            $table->id('bagian_id');
            $table->string('kelas_id', 64);
            $table->integer('urutan');
            $table->string('judul_bagian', 160);
            $table->string('judul_ringkasan', 160)->nullable();
            $table->string('konten_ringkasan', 5000)->nullable();

            $table->foreign('kelas_id')->references('kelas_id')->on('classroom')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bagian_kelas');
    }
};
