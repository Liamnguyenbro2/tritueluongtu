<?php

namespace App\Services;

class SimplePdfExporter
{
    public function build(string $title, array $lines): string
    {
        $safeTitle = $this->pdfText($title);
        $safeLines = array_map(fn ($line) => $this->pdfText((string) $line), $lines);

        $y = 800;
        $content = "BT /F1 16 Tf 50 {$y} Td ({$safeTitle}) Tj ET\n";
        $y -= 30;

        foreach ($safeLines as $line) {
            if ($y < 50) {
                break;
            }

            $content .= "BT /F1 10 Tf 50 {$y} Td ({$line}) Tj ET\n";
            $y -= 16;
        }

        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length ".strlen($content)." >>\nstream\n{$content}endstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $objectNumber = $index + 1;
            $pdf .= "{$objectNumber} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }

        $pdf .= "trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        file_put_contents($tempFile, $pdf);

        return $tempFile;
    }

    private function pdfText(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $ascii = $ascii === false ? $value : $ascii;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\(', '\)', ' ', ' '], $ascii);
    }
}
