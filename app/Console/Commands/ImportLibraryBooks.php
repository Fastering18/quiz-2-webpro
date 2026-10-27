<?php

namespace App\Console\Commands;

use App\Models\Perpustakaan;
use App\Models\User;
use App\Services\FileStorage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('library:import {directory : Folder yang berisi berkas PDF} {--purge : Hapus semua data buku dan objeknya sebelum impor}')]
#[Description('Impor PDF perpustakaan ke object storage (Neon S3 bila dikonfigurasi)')]
class ImportLibraryBooks extends Command
{
    public function handle(FileStorage $files): int
    {
        $directory = (string) $this->argument('directory');
        if (! is_dir($directory)) {
            $this->error('Folder tidak ditemukan: '.$directory);

            return self::FAILURE;
        }

        $publisher = User::query()->where('role', 'admin')->orderBy('user_id')->first()
            ?? User::query()->where('role', 'dosen')->orderBy('user_id')->first();

        if (! $publisher) {
            $this->error('Tidak ada akun admin atau dosen untuk dicatat sebagai pengunggah.');

            return self::FAILURE;
        }

        if ($this->option('purge')) {
            $removed = $this->purge($files);
            $this->info('Buku yang dihapus: '.$removed);
        }

        $paths = glob(rtrim($directory, '\\/').DIRECTORY_SEPARATOR.'*.pdf') ?: [];
        if ($paths === []) {
            $this->warn('Tidak ada berkas PDF di folder tersebut.');

            return self::SUCCESS;
        }

        $diskName = $files->disk();
        $imported = 0;

        foreach ($paths as $path) {
            $name = basename($path);
            $size = filesize($path);
            if ($size === false || $size > ($files->maxKb('library') * 1024)) {
                $this->warn('Lewati, ukuran tidak valid: '.$name);

                continue;
            }

            $header = file_get_contents($path, false, null, 0, 5);
            if ($header !== '%PDF-') {
                $this->warn('Lewati, bukan PDF: '.$name);

                continue;
            }

            $meta = $this->describe($name);
            $bukuId = 'BK_'.strtoupper(Str::random(6));
            $storedName = Str::lower(Str::random(10)).'-'.$files->safeName($name);
            $key = 'library/'.$bukuId.'/'.$storedName;
            $stream = fopen($path, 'rb');

            if ($stream === false) {
                $this->warn('Lewati, berkas tidak terbaca: '.$name);

                continue;
            }

            $written = Storage::disk($diskName)->writeStream($key, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($written === false) {
                $this->error('Gagal menyimpan objek: '.$name);

                return self::FAILURE;
            }

            Perpustakaan::query()->create([
                'buku_id' => $bukuId,
                'judul_buku' => $meta['judul'],
                'penulis' => $meta['penulis'],
                'publisher_id' => $publisher->user_id,
                'tahun' => $meta['tahun'],
                'kategori' => $meta['kategori'],
                'nama_file' => $name,
                'file_dokumen_key' => $key,
                'ukuran_file' => $size,
            ]);
            $imported++;
            $this->line('Tersimpan: '.$meta['judul']);
        }

        $this->info('PDF diimpor: '.$imported.' (disk '.$diskName.').');

        return self::SUCCESS;
    }

    private function purge(FileStorage $files): int
    {
        $removed = 0;
        Perpustakaan::query()->orderBy('buku_id')->each(function (Perpustakaan $book) use ($files, &$removed) {
            $files->delete($book->file_dokumen_key);
            $book->delete();
            $removed++;
        });

        $disk = Storage::disk($files->disk());
        try {
            foreach ($disk->allFiles('library') as $key) {
                $disk->delete($key);
            }
        } catch (\Throwable $e) {
            $this->warn('Sisa objek tidak dapat dibersihkan: '.$e->getMessage());
        }

        return $removed;
    }

    /**
     * @return array{judul: string, penulis: string, kategori: string, tahun: ?int}
     */
    private function describe(string $filename): array
    {
        $judul = trim((string) preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME))));
        $judul = trim((string) preg_replace('/\b97[89]\d{10}\b/', '', $judul));
        $penulis = 'Tidak diketahui';

        if (preg_match('/^(.+?)\s+by\s+(.+)$/i', $judul, $matches) === 1) {
            $judul = trim($matches[1]);
            $penulis = trim((string) preg_replace('/\s*[-–]\s*Pearson.*$/i', '', $matches[2]));
        }

        $kategori = 'Referensi';
        $haystack = strtolower($judul.' '.$filename);
        if (str_contains($haystack, 'network') || str_contains($haystack, 'jaringan')) {
            $kategori = 'Jaringan';
        } elseif (str_contains($haystack, 'math') || str_contains($haystack, 'matematika')) {
            $kategori = 'Matematika';
        }

        return [
            'judul' => Str::limit($judul !== '' ? $judul : 'Buku', 200, ''),
            'penulis' => Str::limit($penulis !== '' ? $penulis : 'Tidak diketahui', 160, ''),
            'kategori' => $kategori,
            'tahun' => null,
        ];
    }
}
