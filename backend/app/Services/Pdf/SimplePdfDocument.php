<?php

namespace App\Services\Pdf;

class SimplePdfDocument
{
    /**
     * @var list<string>
     */
    protected array $commands = [];

    /**
     * @var list<array{name:string,data:string,pixel_width:int,pixel_height:int}>
     */
    protected array $images = [];

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

    public function pngImage(string $path, float $x, float $y, float $width, float $height): bool
    {
        if (!is_file($path) || !function_exists('imagecreatefrompng')) {
            return false;
        }

        $source = @imagecreatefrompng($path);

        if (!$source) {
            return false;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $maxPixels = max(96, (int) ceil(max($width, $height) * 4));
        $scale = min(1, $maxPixels / max($sourceWidth, $sourceHeight));
        $pixelWidth = max(1, (int) round($sourceWidth * $scale));
        $pixelHeight = max(1, (int) round($sourceHeight * $scale));
        $canvas = imagecreatetruecolor($pixelWidth, $pixelHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $pixelWidth, $pixelHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagejpeg($canvas, null, 88);
        $data = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if (!$data) {
            return false;
        }

        $this->addJpegImage($data, $pixelWidth, $pixelHeight, $x, $y, $width, $height);

        return true;
    }

    public function jpegImage(string $path, float $x, float $y, float $width, float $height): bool
    {
        if (!is_file($path)) {
            return false;
        }

        $size = @getimagesize($path);

        if (!$size || ($size[2] ?? null) !== IMAGETYPE_JPEG) {
            return false;
        }

        $data = file_get_contents($path);

        if (!$data) {
            return false;
        }

        $this->addJpegImage($data, (int) $size[0], (int) $size[1], $x, $y, $width, $height);

        return true;
    }

    protected function addJpegImage(
        string $data,
        int $pixelWidth,
        int $pixelHeight,
        float $x,
        float $y,
        float $width,
        float $height
    ): void {
        $name = 'Im' . (count($this->images) + 1);
        $this->images[] = [
            'name' => $name,
            'data' => $data,
            'pixel_width' => $pixelWidth,
            'pixel_height' => $pixelHeight,
        ];

        $this->commands[] = sprintf(
            "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q",
            $width,
            $height,
            $x,
            $y,
            $name
        );
    }

    public function output(): string
    {
        $content = "0.6 w\n";

        foreach ($this->commands as $command) {
            $content .= $command . "\n";
        }

        $imageObjectStart = 6;
        $contentObjectNumber = $imageObjectStart + count($this->images);
        $xObjectResources = '';

        foreach ($this->images as $index => $image) {
            $objectNumber = $imageObjectStart + $index;
            $xObjectResources .= sprintf('/%s %d 0 R ', $image['name'], $objectNumber);
        }

        $resources = '/Font << /F1 4 0 R /F2 5 0 R >>';

        if ($xObjectResources !== '') {
            $resources .= ' /XObject << ' . trim($xObjectResources) . ' >>';
        }

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << {$resources} >> /Contents {$contentObjectNumber} 0 R >> endobj",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj",
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj",
        ];

        foreach ($this->images as $index => $image) {
            $objectNumber = $imageObjectStart + $index;
            $objects[] = sprintf(
                "%d 0 obj << /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >> stream\n%s\nendstream\nendobj",
                $objectNumber,
                $image['pixel_width'],
                $image['pixel_height'],
                strlen($image['data']),
                $image['data']
            );
        }

        $objects[] = "{$contentObjectNumber} 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "endstream\nendobj";

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
