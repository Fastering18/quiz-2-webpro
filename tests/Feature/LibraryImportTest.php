<?php

namespace Tests\Feature;

use App\Models\Perpustakaan;
use App\Services\DirectSmtpTransport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config([
            'filesystems.disks.s3.bucket' => 'test-bucket',
            'filesystems.disks.s3.key' => 'k',
            'filesystems.disks.s3.secret' => 's',
        ]);
        Storage::fake('s3');
    }

    public function test_import_purges_existing_books_and_stores_pdf_on_s3(): void
    {
        $this->assertGreaterThan(0, Perpustakaan::query()->count());

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lib-import-'.uniqid();
        mkdir($directory);
        $pdf = $directory.DIRECTORY_SEPARATOR.'Catatan Kuliah.pdf';
        file_put_contents($pdf, "%PDF-1.4\n%fixture\n");

        try {
            $this->artisan('library:import', [
                'directory' => $directory,
                '--purge' => true,
            ])->assertSuccessful();
        } finally {
            @unlink($pdf);
            @rmdir($directory);
        }

        $this->assertSame(1, Perpustakaan::query()->count());
        $book = Perpustakaan::query()->first();
        $this->assertSame('Catatan Kuliah.pdf', $book->nama_file);
        $this->assertSame('Referensi', $book->kategori);
        $this->assertStringStartsWith('library/'.$book->buku_id.'/', $book->file_dokumen_key);
        Storage::disk('s3')->assertExists($book->file_dokumen_key);
    }

    public function test_direct_smtp_dot_stuffs_and_reads_mail_servers_without_a_relay(): void
    {
        $transport = new DirectSmtpTransport('kelas.example.test', 5);

        $this->assertSame("baris\r\n..titik", $transport->dotStuff("baris\n.titik"));
        $this->assertNotSame('', (string) $transport);
    }
}
