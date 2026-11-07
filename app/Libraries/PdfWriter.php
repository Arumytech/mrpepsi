<?php
namespace App\Libraries;

class PdfWriter {
    public float $pageW;
    public float $pageH;
    public float $lMargin = 42.0;
    public float $tMargin = 42.0;
    public float $rMargin = 42.0;
    public float $bMargin = 48.0;
    public float $y;

    private array $pages = [];
    private string $font = 'F1';
    private float $fontSize = 10.0;
    private float $tR = 0.0;
    private float $tG = 0.0;
    private float $tB = 0.0;
    private ?string $title = null;
    private $pageHeader = null;
    private array $imageObjects = [];  // name => ['data','width','height','filter','cs','bpc']

    private static ?array $wNormal = null;
    private static ?array $wBold = null;

    private const HELV = '278 278 355 556 556 889 667 191 333 333 389 584 278 333 278 278 556 556 556 556 556 556 556 556 556 556 278 278 584 584 584 556 1015 667 667 722 722 667 611 778 722 278 500 667 556 833 722 778 667 778 722 667 611 722 667 944 667 667 611 278 278 278 469 556 333 556 556 500 556 556 278 556 556 222 222 500 222 833 556 556 556 556 333 500 278 556 500 722 500 500 500 334 260 334 584';
    private const HELVB = '278 333 474 556 556 889 722 238 333 333 389 584 278 333 278 278 556 556 556 556 556 556 556 556 556 556 333 333 584 584 584 611 975 722 722 722 722 667 611 778 722 278 556 722 611 833 722 778 667 778 722 667 611 722 667 944 667 667 611 333 278 333 584 556 333 556 611 556 611 556 333 611 611 278 278 556 278 889 611 611 611 611 389 556 333 611 556 778 556 556 500 389 280 389 584';

    public function __construct(string $orientation = 'P', string $size = 'a4') {
        // Ukuran halaman (lebar, tinggi) dalam point.
        // legal/f4 = ukuran HVS LEGAL / Folio Indonesia 215 x 330 mm.
        $sizes = [
            'a4'    => [595.28, 841.89],
            'a3'    => [841.89, 1190.55],
            'legal' => [609.45, 935.43],
            'f4'    => [609.45, 935.43],
        ];
        $dims = $sizes[strtolower($size)] ?? $sizes['a4'];
        $w = $dims[0];
        $h = $dims[1];
        if (strtoupper($orientation) === 'L') {
            $this->pageW = $h;
            $this->pageH = $w;
        } else {
            $this->pageW = $w;
            $this->pageH = $h;
        }
        $this->y = $this->pageH - $this->tMargin;
        $this->pages[] = '';
    }

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function setPageHeader(?callable $cb): void {
        $this->pageHeader = $cb;
    }

    public function usableWidth(): float {
        return $this->pageW - $this->lMargin - $this->rMargin;
    }

    public function setY(float $y): void {
        $this->y = $y;
    }

    public function addPage(): void {
        if (!empty($this->pages)) {
            $this->pages[] = '';
            $this->y = $this->pageH - $this->tMargin;
            if ($this->pageHeader) {
                ($this->pageHeader)($this);
            }
        }
    }

    public function setFont(bool $bold, float $size): void {
        $this->font = $bold ? 'F2' : 'F1';
        $this->fontSize = $size;
    }

    public function setTextColor(float $r, float $g, float $b): void {
        $this->tR = $r;
        $this->tG = $g;
        $this->tB = $b;
    }

    private function op(string $s): void {
        $i = count($this->pages) - 1;
        $this->pages[$i] .= $s . "\n";
    }

    private function n(float $v): string {
        $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        return ($s === '' || $s === '-') ? '0' : $s;
    }

    private function enc(string $s): string {
        $s = str_replace(["\r\n", "\r", "\t"], ' ', $s);
        if (function_exists('iconv')) {
            $out = @iconv('UTF-8', 'CP1252//TRANSLIT', $s);
            if ($out !== false) {
                return $out;
            }
        }
        return mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }

    private static function widths(bool $bold): array {
        if ($bold) {
            if (self::$wBold === null) {
                self::$wBold = array_map('intval', explode(' ', self::HELVB));
            }
            return self::$wBold;
        }
        if (self::$wNormal === null) {
            self::$wNormal = array_map('intval', explode(' ', self::HELV));
        }
        return self::$wNormal;
    }

    public function textWidth(string $s, ?bool $bold = null, ?float $size = null): float {
        $bold = $bold ?? ($this->font === 'F2');
        $size = $size ?? $this->fontSize;
        $enc = $this->enc($s);
        $tab = self::widths($bold);
        $sum = 0;
        $len = strlen($enc);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($enc[$i]);
            $sum += ($c >= 32 && $c <= 126) ? ($tab[$c - 32] ?? 556) : 556;
        }
        return $sum * $size / 1000.0;
    }

    public function wrap(string $text, float $maxW, ?bool $bold = null, ?float $size = null): array {
        $bold = $bold ?? ($this->font === 'F2');
        $size = $size ?? $this->fontSize;
        if ($maxW <= 0) {
            return [''];
        }
        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $para) {
            $words = preg_split('/\s+/u', trim($para), -1, PREG_SPLIT_NO_EMPTY);
            if (!$words) {
                $lines[] = '';
                continue;
            }
            $line = '';
            foreach ($words as $w) {
                $cand = ($line === '') ? $w : $line . ' ' . $w;
                if ($this->textWidth($cand, $bold, $size) <= $maxW) {
                    $line = $cand;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                if ($this->textWidth($w, $bold, $size) <= $maxW) {
                    $line = $w;
                    continue;
                }
                $chunk = '';
                foreach (mb_str_split($w) as $ch) {
                    if ($this->textWidth($chunk . $ch, $bold, $size) <= $maxW) {
                        $chunk .= $ch;
                    } else {
                        $lines[] = $chunk;
                        $chunk = $ch;
                    }
                }
                $line = $chunk;
            }
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        return $lines === [] ? [''] : $lines;
    }

    /**
     * Embed an image file (JPEG, PNG, WEBP, GIF) into the PDF.
     * Returns the internal resource name (e.g. 'Im1') or null on failure.
     */
    public function addImage(string $filePath): ?string {
        if (!is_file($filePath) || !function_exists('getimagesize')) {
            return null;
        }
        $info = @getimagesize($filePath);
        if (!$info) {
            return null;
        }
        $imgW = (int)$info[0];
        $imgH = (int)$info[1];
        $mime = (string)($info['mime'] ?? '');

        if (in_array($mime, ['image/jpeg', 'image/jpg'], true)) {
            $data = file_get_contents($filePath);
            if ($data === false) return null;
        } elseif (in_array($mime, ['image/png','image/gif','image/webp'], true) && function_exists('imagecreatetruecolor')) {
            $gd = null;
            if ($mime === 'image/png')        $gd = @imagecreatefrompng($filePath);
            elseif ($mime === 'image/gif')    $gd = @imagecreatefromgif($filePath);
            elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $gd = @imagecreatefromwebp($filePath);
            if (!$gd) return null;
            $bg = imagecreatetruecolor($imgW, $imgH);
            if (!$bg) { imagedestroy($gd); return null; }
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagecopy($bg, $gd, 0, 0, 0, 0, $imgW, $imgH);
            imagedestroy($gd);
            ob_start();
            imagejpeg($bg, null, 92);
            $data = ob_get_clean();
            imagedestroy($bg);
            if ($data === '' || $data === false) return null;
        } else {
            return null;
        }

        $name = 'Im' . (count($this->imageObjects) + 1);
        $this->imageObjects[$name] = [
            'data'   => $data,
            'width'  => $imgW,
            'height' => $imgH,
            'filter' => '/DCTDecode',
            'cs'     => '/DeviceRGB',
            'bpc'    => 8,
        ];
        return $name;
    }

    /**
     * Draw a previously added image.
     * (x, yBottom) is the bottom-left corner of the image in PDF coordinates.
     */
    public function drawImage(string $name, float $x, float $yBottom, float $w, float $h): void {
        if (!isset($this->imageObjects[$name])) return;
        $this->op(sprintf(
            'q %s 0 0 %s %s %s cm /%s Do Q',
            $this->n($w), $this->n($h),
            $this->n($x), $this->n($yBottom),
            $name
        ));
    }

    private function textOp(float $x, float $y, string $s, bool $bold, float $size, float $r, float $g, float $b): string {
        $s = $this->enc($s);
        if ($s === '') {
            return '';
        }
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        return sprintf(
            'BT /%s %s Tf %s %s %s rg %s %s Td (%s) Tj ET',
            $bold ? 'F2' : 'F1',
            $this->n($size),
            $this->n($r), $this->n($g), $this->n($b),
            $this->n($x), $this->n($y),
            $s
        );
    }

    public function text(float $x, float $y, string $s): void {
        $op = $this->textOp($x, $y, $s, $this->font === 'F2', $this->fontSize, $this->tR, $this->tG, $this->tB);
        if ($op !== '') {
            $this->op($op);
        }
    }

    public function fillRect(float $x, float $y, float $w, float $h, float $r, float $g, float $b): void {
        $this->op(sprintf('%s %s %s rg %s %s %s %s re f', $this->n($r), $this->n($g), $this->n($b), $this->n($x), $this->n($y), $this->n($w), $this->n($h)));
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $r = 0.78, float $g = 0.81, float $b = 0.86, float $width = 0.6): void {
        $this->op(sprintf('%s w %s %s %s RG %s %s m %s %s l S', $this->n($width), $this->n($r), $this->n($g), $this->n($b), $this->n($x1), $this->n($y1), $this->n($x2), $this->n($y2)));
    }

    private function vLine(float $x, float $y1, float $y2, float $r = 0.78, float $g = 0.81, float $b = 0.86, float $width = 0.6): void {
        $this->line($x, $y1, $x, $y2, $r, $g, $b, $width);
    }

    public function table(array $columns, array $rows, array $opt = []): void {
        $fs = (float)($opt['fontSize'] ?? 9);
        $hfs = (float)($opt['headerFontSize'] ?? 9);
        $pad = (float)($opt['pad'] ?? 4);
        $lead = (float)($opt['leading'] ?? 1.32);
        $x0 = (float)($opt['x'] ?? $this->lMargin);
        $hb = (array)($opt['headerBg'] ?? [0.09, 0.16, 0.31]);
        $zebra = (array)($opt['zebra'] ?? [0.955, 0.965, 0.98]);
        $grid = (array)($opt['grid'] ?? [0.80, 0.83, 0.88]);
        $spanColor = (array)($opt['spanColor'] ?? [0.45, 0.48, 0.55]);
        $totalW = (float)array_sum(array_column($columns, 'w'));

        $drawHeader = function () use ($columns, $x0, $hfs, $pad, $lead, $hb, $totalW, $grid) {
            $this->setFont(true, $hfs);
            $maxLines = 1;
            foreach ($columns as $c) {
                $maxLines = max($maxLines, count($this->wrap((string)($c['label'] ?? ''), $c['w'] - 2 * $pad, true, $hfs)));
            }
            $h = $maxLines * $lead * $hfs + 2 * $pad;
            $top = $this->y;
            $right = $x0 + $totalW;
            $this->fillRect($x0, $top - $h, $totalW, $h, $hb[0], $hb[1], $hb[2]);
            $this->setFont(true, $hfs);
            $this->setTextColor(1, 1, 1);
            $cx = $x0;
            foreach ($columns as $c) {
                $lines = $this->wrap((string)($c['label'] ?? ''), $c['w'] - 2 * $pad, true, $hfs);
                $blockH = count($lines) * $lead * $hfs;
                foreach ($lines as $li => $lineTxt) {
                    $tw = $this->textWidth($lineTxt, true, $hfs);
                    $tx = $cx + $this->alignX((float)$c['w'], $tw, $c['align'] ?? 'left', $pad);
                    $baseline = $top - ($h - $blockH) / 2 - $hfs * 0.80 - $li * $lead * $hfs;
                    $this->text($tx, $baseline, $lineTxt);
                }
                $cx += $c['w'];
            }
            $this->setTextColor(0, 0, 0);
            $this->line($x0, $top, $right, $top, $grid[0], $grid[1], $grid[2]);
            $this->line($x0, $top - $h, $right, $top - $h, $grid[0], $grid[1], $grid[2]);
            $bx = $x0;
            foreach ($columns as $c) {
                $this->vLine($bx, $top, $top - $h, $grid[0], $grid[1], $grid[2]);
                $bx += $c['w'];
            }
            $this->vLine($right, $top, $top - $h, $grid[0], $grid[1], $grid[2]);
            $this->y = $top - $h;
        };

        $drawHeader();

        $rowIndex = 0;
        foreach ($rows as $row) {
            if (is_string($row)) {
                $lines = $this->wrap($row, $totalW - 2 * $pad, false, $fs);
                $h = count($lines) * $lead * $fs + 2 * $pad;
                if ($this->y - $h < $this->bMargin) {
                    $this->addPage();
                    $drawHeader();
                }
                $top = $this->y;
                $bottom = $top - $h;
                $blockH = count($lines) * $lead * $fs;
                $this->setFont(false, $fs);
                $this->setTextColor($spanColor[0], $spanColor[1], $spanColor[2]);
                foreach ($lines as $li => $lineTxt) {
                    $tw = $this->textWidth($lineTxt, false, $fs);
                    $baseline = $top - ($h - $blockH) / 2 - $fs * 0.80 - $li * $lead * $fs;
                    $this->text($x0 + ($totalW - $tw) / 2, $baseline, $lineTxt);
                }
                $this->setTextColor(0, 0, 0);
                $this->line($x0, $top, $x0 + $totalW, $top, $grid[0], $grid[1], $grid[2]);
                $this->line($x0, $bottom, $x0 + $totalW, $bottom, $grid[0], $grid[1], $grid[2]);
                $this->vLine($x0, $top, $bottom, $grid[0], $grid[1], $grid[2]);
                $this->vLine($x0 + $totalW, $top, $bottom, $grid[0], $grid[1], $grid[2]);
                $this->y = $bottom;
                continue;
            }

            $cells = [];
            $maxLines = 1;
            foreach ($columns as $i => $c) {
                $val = (string)($row[$i] ?? '');
                $cellLines = $this->wrap($val, $c['w'] - 2 * $pad, $c['bold'] ?? false, $fs);
                $cells[] = $cellLines;
                $maxLines = max($maxLines, count($cellLines));
            }
            $h = $maxLines * $lead * $fs + 2 * $pad;
            if ($this->y - $h < $this->bMargin) {
                $this->addPage();
                $drawHeader();
            }
            $top = $this->y;
            $bottom = $top - $h;

            if ($rowIndex % 2 === 1) {
                $this->fillRect($x0, $bottom, $totalW, $h, $zebra[0], $zebra[1], $zebra[2]);
            }

            $bx = $x0;
            foreach ($columns as $ci => $c) {
                $lines = $cells[$ci];
                $blockH = count($lines) * $lead * $fs;
                $this->setFont($c['bold'] ?? false, $fs);
                $this->setTextColor(0.13, 0.16, 0.22);
                foreach ($lines as $li => $lineTxt) {
                    $tw = $this->textWidth($lineTxt, $c['bold'] ?? false, $fs);
                    $cx = $this->alignX((float)$c['w'], $tw, $c['align'] ?? 'left', $pad);
                    $baseline = $top - ($h - $blockH) / 2 - $fs * 0.80 - $li * $lead * $fs;
                    $this->text($bx + $cx, $baseline, $lineTxt);
                }
                $bx += $c['w'];
            }

            $this->line($x0, $bottom, $x0 + $totalW, $bottom, $grid[0], $grid[1], $grid[2]);
            $bx = $x0;
            foreach ($columns as $c) {
                $this->vLine($bx, $top, $bottom, $grid[0], $grid[1], $grid[2]);
                $bx += $c['w'];
            }
            $this->vLine($x0 + $totalW, $top, $bottom, $grid[0], $grid[1], $grid[2]);

            $this->y = $bottom;
            $rowIndex++;
        }
    }

    private function alignX(float $colW, float $textW, string $align, float $pad): float {
        if ($align === 'center') {
            return ($colW - $textW) / 2;
        }
        if ($align === 'right') {
            return $colW - $pad - $textW;
        }
        return $pad;
    }

    public function output(): string {
        $count    = count($this->pages);
        $total    = $count;
        $imgCount = count($this->imageObjects);

        $footerFont = 8;
        for ($i = 0; $i < $count; $i++) {
            $label = 'Halaman ' . ($i + 1) . ' dari ' . $total;
            $w = $this->textWidth($label, false, $footerFont);
            $this->pages[$i] .= $this->lineOp($this->lMargin, 38, $this->pageW - $this->rMargin, 38, 0.85, 0.87, 0.90, 0.5) . "\n";
            $this->pages[$i] .= $this->textOp(($this->pageW - $w) / 2, 26, $label, false, $footerFont, 0.55, 0.58, 0.63) . "\n";
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        // Page dict object numbers: 6 + imgCount + 2*i
        $kids = [];
        for ($i = 0; $i < $count; $i++) {
            $kids[] = (6 + $imgCount + 2 * $i) . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $count . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        // Image XObjects: objects 5..(4+imgCount)
        $imgNames = array_keys($this->imageObjects);
        foreach ($imgNames as $idx => $name) {
            $img     = $this->imageObjects[$name];
            $objNum  = 5 + $idx;
            $imgData = $img['data'];
            $objects[$objNum] = '<< /Type /XObject /Subtype /Image'
                . ' /Width '            . $img['width']
                . ' /Height '           . $img['height']
                . ' /ColorSpace '       . $img['cs']
                . ' /BitsPerComponent ' . $img['bpc']
                . ' /Filter '           . $img['filter']
                . ' /Length '           . strlen($imgData)
                . " >>\nstream\n" . $imgData . 'endstream';
        }

        // Build /XObject resource reference string
        $xobjRes = '';
        if ($imgCount > 0) {
            $parts = [];
            foreach ($imgNames as $idx => $name) {
                $parts[] = '/' . $name . ' ' . (5 + $idx) . ' 0 R';
            }
            $xobjRes = ' /XObject << ' . implode(' ', $parts) . ' >>';
        }

        // Page content + page dict objects
        for ($i = 0; $i < $count; $i++) {
            $contentNum = 5 + $imgCount + 2 * $i;
            $pageNum    = 6 + $imgCount + 2 * $i;
            $content    = $this->pages[$i];
            $objects[$contentNum] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream';
            $objects[$pageNum]    = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
                . $this->n($this->pageW) . ' ' . $this->n($this->pageH)
                . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>'
                . $xobjRes
                . ' >> /Contents ' . $contentNum . ' 0 R >>';
        }

        $infoNum  = 5 + $imgCount + 2 * $count;
        $titleHex = '';
        if ($this->title !== null) {
            $utf16 = @iconv('UTF-8', 'UTF-16BE', $this->title);
            if ($utf16 !== false) {
                $titleHex = ' /Title <FEFF' . strtoupper(bin2hex($utf16)) . '>';
            }
        }
        $off = date('O');
        $offStr = substr($off, 0, 3) . "'" . substr($off, 3, 2) . "'";
        $objects[$infoNum] = '<<' . $titleHex
            . ' /Producer (MR.PEPSI PDF Writer)'
            . ' /Creator (MR.PEPSI Schedule)'
            . ' /CreationDate (D:' . date('YmdHis') . $offStr . ') >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 " . $size . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d %05d n ', $offsets[$i], 0) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . $size . " /Root 1 0 R /Info " . $infoNum . " 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }

    private function lineOp(float $x1, float $y1, float $x2, float $y2, float $r, float $g, float $b, float $width): string {
        return sprintf('%s w %s %s %s RG %s %s m %s %s l S', $this->n($width), $this->n($r), $this->n($g), $this->n($b), $this->n($x1), $this->n($y1), $this->n($x2), $this->n($y2));
    }
}
