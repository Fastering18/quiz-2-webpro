<?php

namespace App\Services;

/**
 * Tiny dependency-free PDF writer (A4, Helvetica, text + lines). Enough for printable documents such as the KRS.
 * Coordinates are in points from the top-left corner of the page.
 */
class SimplePdf
{
    public const W = 595.28;

    public const H = 841.89;

    /** @var list<string> page content streams */
    private array $pages = [];

    private string $cur = '';

    public function __construct()
    {
        $this->addPage();
    }

    public function addPage(): void
    {
        if ($this->cur !== '') {
            $this->pages[] = $this->cur;
        }
        $this->cur = '';
    }

    public function text(float $x, float $y, string $text, float $size = 10, bool $bold = false): void
    {
        $this->cur .= sprintf("BT /%s %.2f Tf %.2f %.2f Td (%s) Tj ET\n", $bold ? 'F2' : 'F1', $size, $x, self::H - $y, $this->esc($text));
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.6): void
    {
        $this->cur .= sprintf("%.2f w %.2f %.2f m %.2f %.2f l S\n", $width, $x1, self::H - $y1, $x2, self::H - $y2);
    }

    public function rect(float $x, float $y, float $w, float $h, float $width = 0.6): void
    {
        $this->cur .= sprintf("%.2f w %.2f %.2f %.2f %.2f re S\n", $width, $x, self::H - $y - $h, $w, $h);
    }

    /** Approximate text width in points for right-aligning (Helvetica average glyph ~0.5em). */
    public function width(string $text, float $size = 10): float
    {
        return strlen($this->latin1($text)) * $size * 0.5;
    }

    public function output(): string
    {
        $pages = $this->pages;
        if ($this->cur !== '' || $pages === []) {
            $pages[] = $this->cur;
        }

        $objs = [];
        $n = count($pages);
        // 1 catalog, 2 pages, 3 font regular, 4 font bold, then page/content pairs
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        for ($i = 0; $i < $n; $i++) {
            $kids[] = (5 + $i * 2).' 0 R';
        }
        $objs[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$n.' >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        foreach ($pages as $i => $content) {
            $pageId = 5 + $i * 2;
            $objs[$pageId] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', self::W, self::H, $pageId + 1);
            $objs[$pageId + 1] = '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream';
        }

        $out = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $id => $body) {
            $offsets[$id] = strlen($out);
            $out .= $id." 0 obj\n".$body."\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $out .= sprintf("%010d 00000 n \n", $off);
        }
        $out .= 'trailer << /Size '.(count($objs) + 1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF";

        return $out;
    }

    private function latin1(string $s): string
    {
        $s = strtr($s, ['–' => '-', '—' => '-', '“' => '"', '”' => '"', '‘' => "'", '’' => "'", '…' => '...']);
        $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);

        return $r === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $r;
    }

    private function esc(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $this->latin1($s));
    }
}
