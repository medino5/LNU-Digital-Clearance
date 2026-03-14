<?php

namespace App\Services\Pdf;

class SimplePdfDocument
{
    /**
     * @var list<string>
     */
    protected array $commands = [];

    public function text(float $x, float $y, string $text, int $size = 12, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->commands[] = sprintf(
            "BT /%s %d Tf %.2F %.2F Td (%s) Tj ET",
            $font,
            $size,
            $x,
            $y,
            $this->escape($text)
        );
    }

    public function wrappedText(
        float $x,
        float $y,
        float $width,
        string $text,
        int $size = 12,
        bool $bold = false,
        float $leading = 16
    ): float {
        $maxChars = max(12, (int) floor($width / max(5.4, $size * 0.5)));
        $lines = $this->wrap($text, $maxChars);
        $currentY = $y;

        foreach ($lines as $line) {
            $this->text($x, $currentY, $line, $size, $bold);
            $currentY -= $leading;
        }

        return $currentY;
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->commands[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2);
    }

    public function output(): string
    {
        $content = "0.6 w\n";

        foreach ($this->commands as $command) {
            $content .= $command . "\n";
        }

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >> endobj",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj",
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj",
            "6 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "endstream\nendobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefOffset = strlen($pdf);

        $pdf .= "xref\n0 " . count($offsets) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < count($offsets); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer << /Size " . count($offsets) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    protected function wrap(string $text, int $maxChars): array
    {
        $clean = preg_replace('/\s+/', ' ', trim($text)) ?? '';

        if ($clean === '') {
            return [''];
        }

        $words = explode(' ', $clean);
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;

            if (strlen($candidate) <= $maxChars) {
                $line = $candidate;
                continue;
            }

            if ($line !== '') {
                $lines[] = $line;
            }

            $line = $word;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    protected function escape(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('(', '\(', $value);
        $value = str_replace(')', '\)', $value);

        return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
    }
}
