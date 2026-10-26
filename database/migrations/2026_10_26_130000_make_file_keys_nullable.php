<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seeded demo rows may have no stored file (e.g. when object storage is unavailable while seeding).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materi_kelas', fn (Blueprint $t) => $t->string('file_materi_key')->nullable()->change());
        Schema::table('pengumpulan_tugas', fn (Blueprint $t) => $t->string('file_jawaban_key')->nullable()->change());
        Schema::table('perpustakaan', fn (Blueprint $t) => $t->string('file_dokumen_key')->nullable()->change());
    }

    public function down(): void
    {
        // intentionally left nullable: rows without files may exist
    }
};
