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
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->string('nrp', 14)->primary();
            $table->string('user_id', 16)->unique();
            $table->string('nip_wali', 18);
            $table->string('departemen', 80);
            $table->integer('semester_aktif');
            $table->string('status', 20);

            $table->foreign('user_id')->references('user_id')->on('pengguna');
            $table->foreign('nip_wali')->references('nip')->on('dosen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
