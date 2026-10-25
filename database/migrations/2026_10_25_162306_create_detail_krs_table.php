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
        Schema::create('detail_krs', function (Blueprint $table) {
            $table->id('detail_id');
            $table->string('krs_id', 16);
            $table->string('kelas_id', 12);
            $table->integer('nilai_akhir')->nullable();

            $table->foreign('krs_id')->references('krs_id')->on('krs')->cascadeOnDelete();
            $table->foreign('kelas_id')->references('kelas_id')->on('classroom');
            $table->unique(['krs_id', 'kelas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_krs');
    }
};
