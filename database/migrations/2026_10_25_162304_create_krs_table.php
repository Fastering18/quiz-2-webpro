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
        Schema::create('krs', function (Blueprint $table) {
            $table->string('krs_id', 64)->primary();
            $table->string('nrp', 32);
            $table->integer('semester');
            $table->string('semester_tahun', 12);
            $table->string('status_acc', 20);
            $table->date('tanggal_cetak')->nullable();

            $table->foreign('nrp')->references('nrp')->on('mahasiswa');
            $table->unique(['nrp', 'semester_tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('krs');
    }
};
