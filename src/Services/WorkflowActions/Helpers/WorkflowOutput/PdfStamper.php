<?php

namespace Taurus\Workflow\Services\WorkflowActions\Helpers\WorkflowOutput;

use setasign\Fpdi\Fpdi;

class PdfStamper
{
    /**
     * Stamp placeholder values onto an existing PDF at stored coordinates.
     *
     * Requires the source PDF to be PDF v1.4 compatible (no object streams).
     * PDFs uploaded through the email-builder-backend are normalized via pikepdf
     * at upload time so this holds true.
     *
     * @param  string  $pdfContent  Raw PDF binary content (must be v1.4 compatible).
     * @param  array  $pdfPlaceholders  Array of placeholder definitions: { page, x, y, placeholderText, fontSize }.
     *                                  x and y are percentages (0–100) of page dimensions.
     * @param  array  $resolvedValues  Associative array mapping placeholderText => actual value.
     * @return string The stamped PDF binary content.
     */
    public static function stamp(string $pdfContent, array $pdfPlaceholders, array $resolvedValues): string
    {
        // If no placeholders, return the PDF as-is — nothing to stamp
        if (empty($pdfPlaceholders)) {
            return $pdfContent;
        }

        // FPDI needs a file path (setSourceFile), so dump the bytes to a temp file
        $tempSource = tempnam(sys_get_temp_dir(), 'pdf_src_');
        file_put_contents($tempSource, $pdfContent);

        try {
            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($tempSource);

            // Group placeholders by page number so we stamp each page once
            $placeholdersByPage = [];
            foreach ($pdfPlaceholders as $placeholder) {
                $page = (int) ($placeholder['page'] ?? 1);
                $placeholdersByPage[$page][] = $placeholder;
            }

            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                $templateId = $pdf->importPage($pageNum);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

                if (empty($placeholdersByPage[$pageNum])) {
                    continue;
                }

                foreach ($placeholdersByPage[$pageNum] as $placeholder) {
                    $text = $resolvedValues[$placeholder['placeholderText']] ?? '';
                    if ($text === '') {
                        continue;
                    }

                    $fontSize = (int) ($placeholder['fontSize'] ?? 12);
                    $xPct = (float) ($placeholder['x'] ?? 0);
                    $yPct = (float) ($placeholder['y'] ?? 0);

                    $x = ($xPct / 100) * $size['width'];
                    $y = ($yPct / 100) * $size['height'];

                    $pdf->SetFont('Helvetica', '', $fontSize);
                    $pdf->SetTextColor(0, 0, 0);

                    // FPDF's Text() positions y at the text baseline, but the
                    // frontend stores the click point as the chip's top-left corner.
                    // Shift the baseline down by the Helvetica cap-height (≈72% of
                    // the em size) so the visual top of the rendered characters
                    // aligns with the stored position.
                    // em size in mm = fontSize (pts) × 0.3528 (pt→mm conversion).
                    $capHeightMm = $fontSize * 0.3528 * 0.72;
                    $pdf->Text($x, $y + $capHeightMm, $text);
                }
            }

            return $pdf->Output('S');
        } finally {
            @unlink($tempSource);
        }
    }
}
