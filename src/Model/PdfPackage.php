<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Model;

use mikehaertl\pdftk\Pdf;
use Psr\Log\LoggerInterface;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use SilverStripe\Core\Injector\Injector;

/**
 * Builds the merged Kinderzuschlag PDF: fills + flattens every part with its own field data, keeps the configured
 * page range of each part, merges them in the order they were added and stamps signatures / white masks
 * (flattened push buttons) on the pages of the merged document.
 *
 * Positions are in mm from the top-left corner and refer to the page number of the SOURCE document of a part.
 */
final class PdfPackage
{
    /** @var array<int, array{template: string, data: array<string, mixed>, pages: array<int, int>, stamps: array<int, array<string, float|int>>, masks: array<int, array<string, float|int>>, texts: array<int, array<string, mixed>>}> */
    private array $parts = [];

    /** @var array<int, string> */
    private array $tempFiles = [];

    /**
     * Characters pdftk cannot render without the replacement font are transliterated (fallback only).
     */
    private const TRANSLITERATION = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss',
        'đ' => 'd', 'Đ' => 'D', 'ł' => 'l', 'Ł' => 'L', 'ı' => 'i', 'ø' => 'o', 'Ø' => 'O', 'æ' => 'ae', 'Æ' => 'Ae',
        'œ' => 'oe', 'Œ' => 'Oe', '„' => '"', '“' => '"', '”' => '"', '‚' => "'", '‘' => "'", '’' => "'", '–' => '-', '—' => '-',
        '€' => 'EUR',
    ];

    public function __construct(private readonly ?string $replacementFont = null)
    {
    }

    /**
     * @param array<string, mixed> $data PDF field => value
     * @param string $pages "all", "2-end", "2-3", "1"
     * @param array<int, array<string, float|int>> $stamps [['page' => n, 'x' => mm, 'y' => mm, 'width' => mm], ...]
     * @param array<int, array<string, float|int>> $masks [['page' => n, 'x' => mm, 'y' => mm, 'width' => mm, 'height' => mm], ...]
     * @param array<int, array<string, mixed>> $texts [['page' => n, 'x' => mm (centre), 'y' => mm (baseline), 'text' => 'A', 'size' => pt], ...]
     *                                                ASCII only (FPDF core font) - used for comb cells (IBAN, BIC)
     */
    public function add(string $template, array $data, string $pages = 'all', array $stamps = [], array $masks = [], array $texts = []): void
    {
        if (!is_file($template)) {
            throw new RuntimeException('PDF template not found: ' . $template);
        }

        $this->parts[] = [
            'template' => $template,
            'data' => $data,
            'pages' => self::pageList($pages, self::pageCount($template)),
            'stamps' => $stamps,
            'masks' => $masks,
            'texts' => $texts,
        ];
    }

    public function count(): int
    {
        return count($this->parts);
    }

    /**
     * @return string path of the finished PDF (caller deletes it when done)
     */
    public function build(?string $signaturePng): string
    {
        if ($this->parts === []) {
            throw new RuntimeException('No PDF parts to build.');
        }

        try {
            $files = [];
            foreach ($this->parts as $part) {
                $files[] = $this->fill($part['template'], $part['data']);
            }

            $merged = $this->merge($files);

            return $this->overlay($merged, $signaturePng);
        } finally {
            foreach ($this->tempFiles as $file) {
                @unlink($file);
            }
            $this->tempFiles = [];
        }
    }

    /**
     * Fills + flattens into a temp file. The PDFs use fixed font sizes; pdftk's own appearance generation drops
     * characters outside Windows-1252 (ž, ć, đ ...), so a replacement font is passed. pdftk builds without
     * "replacement_font" (pdftk < 3.0) fall back to an ASCII transliteration.
     *
     * @param array<string, mixed> $data
     */
    private function fill(string $template, array $data): string
    {
        $target = $this->tempPath('filled');

        $pdf = $this->newFilledPdf($template, $data, $this->replacementFont);
        if ($pdf->saveAs($target)) {
            return $target;
        }

        $error = $pdf->getError();
        if ($this->replacementFont !== null) {
            Injector::inst()->get(LoggerInterface::class)->warning(
                'Kinderzuschlag: pdftk fill with replacement_font failed, retrying with ASCII transliteration: ' . $error
            );
            $pdf = $this->newFilledPdf($template, $data, null);
            if ($pdf->saveAs($target)) {
                return $target;
            }
            $error = $pdf->getError();
        }

        throw new RuntimeException('pdftk error: ' . $error);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function newFilledPdf(string $template, array $data, ?string $font): Pdf
    {
        $values = [];
        foreach ($data as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $value = (string) $value;
            $values[$field] = $font === null ? self::toAscii($value) : $value;
        }

        $pdf = new Pdf($template);
        $pdf->fillForm($values)
            ->needAppearances();

        if ($font !== null) {
            $pdf->replacementFont($font);
        }

        return $pdf->flatten();
    }

    /**
     * @param array<int, string> $files filled parts, same order as $this->parts
     */
    private function merge(array $files): string
    {
        $handles = [];
        foreach ($files as $index => $file) {
            $handles[self::handle($index)] = $file;
        }

        $pdf = new Pdf($handles);
        foreach ($this->parts as $index => $part) {
            foreach (self::ranges($part['pages']) as [$from, $to]) {
                $pdf->cat($from, $to, self::handle($index));
            }
        }

        $target = $this->tempPath('merged');
        if (!$pdf->saveAs($target)) {
            throw new RuntimeException('pdftk merge error: ' . $pdf->getError());
        }

        return $target;
    }

    /**
     * White masks + signatures on the merged document (page numbers translated from the source documents).
     */
    private function overlay(string $merged, ?string $signaturePng): string
    {
        $stampsByPage = [];
        $masksByPage = [];
        $textsByPage = [];
        $offset = 0;

        foreach ($this->parts as $part) {
            $position = array_flip($part['pages']); // source page => index within the kept pages
            foreach ($part['masks'] as $mask) {
                if (isset($position[(int) $mask['page']])) {
                    $masksByPage[$offset + $position[(int) $mask['page']] + 1][] = $mask;
                }
            }
            foreach ($part['texts'] as $text) {
                if (isset($position[(int) $text['page']])) {
                    $textsByPage[$offset + $position[(int) $text['page']] + 1][] = $text;
                }
            }
            if ($signaturePng !== null) {
                foreach ($part['stamps'] as $stamp) {
                    if (isset($position[(int) $stamp['page']])) {
                        $stampsByPage[$offset + $position[(int) $stamp['page']] + 1][] = $stamp;
                    }
                }
            }
            $offset += count($part['pages']);
        }

        if ($stampsByPage === [] && $masksByPage === [] && $textsByPage === []) {
            $target = $this->tempPath('result');
            copy($merged, $target);
            $this->forget($target);

            return $target;
        }

        $fpdi = new Fpdi();
        $fpdi->SetAutoPageBreak(false);
        $pageCount = $fpdi->setSourceFile($merged);

        for ($i = 1; $i <= $pageCount; $i++) {
            $template = $fpdi->importPage($i);
            $size = $fpdi->getTemplateSize($template);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($template, 0, 0, $size['width'], $size['height']);

            foreach ($masksByPage[$i] ?? [] as $mask) {
                $fpdi->SetFillColor(255, 255, 255);
                $fpdi->Rect((float) $mask['x'], (float) $mask['y'], (float) $mask['width'], (float) $mask['height'], 'F');
            }

            foreach ($textsByPage[$i] ?? [] as $text) {
                $value = (string) preg_replace('/[^\x20-\x7E]/', '', (string) $text['text']);
                $fpdi->SetFont('Helvetica', '', (float) ($text['size'] ?? 9));
                $fpdi->SetTextColor(0, 0, 0);
                $fpdi->Text((float) $text['x'] - $fpdi->GetStringWidth($value) / 2, (float) $text['y'], $value);
            }

            foreach ($stampsByPage[$i] ?? [] as $stamp) {
                $fpdi->Image((string) $signaturePng, (float) $stamp['x'], (float) $stamp['y'], (float) $stamp['width'], 0, 'PNG');
            }
        }

        $target = $this->tempPath('result');
        $fpdi->Output('F', $target);
        $this->forget($target);

        return $target;
    }

    /** "A", "B", ... "Z", "AA", "AB", ... (pdftk handles are upper-case letters only). */
    private static function handle(int $index): string
    {
        $handle = '';
        $index++;
        while ($index > 0) {
            $index--;
            $handle = chr(65 + $index % 26) . $handle;
            $index = intdiv($index, 26);
        }

        return $handle;
    }

    /**
     * @return array<int, int>
     */
    private static function pageList(string $pages, int $pageCount): array
    {
        $pages = trim(strtolower($pages));
        if ($pages === '' || $pages === 'all') {
            return range(1, $pageCount);
        }

        $list = [];
        foreach (explode(',', $pages) as $chunk) {
            [$from, $to] = array_pad(explode('-', trim($chunk), 2), 2, null);
            $from = (int) $from;
            $to = $to === null ? $from : ($to === 'end' ? $pageCount : (int) $to);
            for ($page = max(1, $from); $page <= min($to, $pageCount); $page++) {
                $list[] = $page;
            }
        }

        return $list;
    }

    /**
     * Consecutive pages as [from, to] ranges for pdftk cat.
     *
     * @param array<int, int> $pages
     * @return array<int, array{0: int, 1: int}>
     */
    private static function ranges(array $pages): array
    {
        $ranges = [];
        foreach ($pages as $page) {
            $last = count($ranges) - 1;
            if ($last >= 0 && $ranges[$last][1] === $page - 1) {
                $ranges[$last][1] = $page;
                continue;
            }
            $ranges[] = [$page, $page];
        }

        return $ranges;
    }

    /**
     * Page count via pdftk (the free FPDI parser cannot read the compressed originals of the Familienkasse).
     */
    private static function pageCount(string $file): int
    {
        $data = (new Pdf($file))->getData();
        $count = $data === false ? 0 : (int) ($data['NumberOfPages'] ?? 0);

        if ($count < 1) {
            throw new RuntimeException('Cannot read the page count of ' . $file);
        }

        return $count;
    }

    private static function toAscii(string $value): string
    {
        $value = strtr($value, self::TRANSLITERATION);

        if (class_exists(\Normalizer::class)) {
            $value = (string) preg_replace('/\p{Mn}+/u', '', (string) \Normalizer::normalize($value, \Normalizer::FORM_D));
        }

        return (string) preg_replace('/[^\x20-\x7E\n]/', '', $value);
    }

    private function tempPath(string $label): string
    {
        $path = sys_get_temp_dir() . '/kinderzuschlag_' . $label . '_' . uniqid('', true) . '.pdf';
        $this->tempFiles[] = $path;

        return $path;
    }

    /** The result must survive build()'s cleanup. */
    private function forget(string $path): void
    {
        $this->tempFiles = array_values(array_filter($this->tempFiles, static fn (string $file): bool => $file !== $path));
    }
}
