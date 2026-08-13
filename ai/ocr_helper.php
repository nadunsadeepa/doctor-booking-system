<?php
/**
 * ai/ocr_helper.php
 * -----------------------------------------------------------
 * Module 15 - AI Report Scanner (step 1: OCR)
 * Tesseract OCR — free, runs locally, no API key/billing.
 *
 * Windows install: https://github.com/UB-Mannheim/tesseract/wiki
 *   (installs to C:\Program Files\Tesseract-OCR\tesseract.exe by default)
 * Linux:  sudo apt install tesseract-ocr
 * Mac:    brew install tesseract
 *
 * Set TESSERACT_PATH in config/ai_config.php to match your install.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/ai_config.php';

/**
 * Extract text from an image file using the Tesseract CLI.
 * Throws Exception if Tesseract isn't found/working or no text
 * is found.
 */
function ocr_extract_text(string $imagePath): string
{
    if (!function_exists('exec')) {
        throw new Exception('PHP exec() is disabled on this server -- Tesseract OCR needs it to run.');
    }

    if (!file_exists($imagePath)) {
        throw new Exception('Report file not found on disk.');
    }

    // tempnam() gives us a unique base path; Tesseract appends ".txt" to it.
    $outputBase = tempnam(sys_get_temp_dir(), 'ocr_');
    if ($outputBase === false) {
        throw new Exception('Could not create a temporary file for OCR output.');
    }
    @unlink($outputBase); // Tesseract creates "<base>.txt" itself -- remove the empty placeholder first

    $cmd = escapeshellarg(TESSERACT_PATH) . ' '
         . escapeshellarg($imagePath) . ' '
         . escapeshellarg($outputBase) . ' '
         . '-l ' . escapeshellarg(TESSERACT_LANG) . ' 2>&1';

    exec($cmd, $output, $returnCode);

    $outputFile = $outputBase . '.txt';

    if ($returnCode !== 0 || !file_exists($outputFile)) {
        throw new Exception(
            'Tesseract could not run (return code ' . $returnCode . '). '
            . 'Check TESSERACT_PATH in config/ai_config.php is correct. Details: '
            . implode(' ', $output)
        );
    }

    $text = file_get_contents($outputFile);
    @unlink($outputFile);

    if (trim($text) === '') {
        throw new Exception('No readable text was found in this image. Make sure the photo is clear, well-lit, and the text is not blurry or cut off.');
    }

    return $text;
}
