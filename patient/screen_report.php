<?php
/**
 * patient/screen_report.php
 * -----------------------------------------------------------
 * Module 15 - AI Report Scanner
 * POST-only. Runs OCR -> AI classification for one report the
 * patient owns, then stores the result. If OCR/AI isn't
 * configured or the call fails, nothing is written to the DB
 * (the report just stays "Not screened yet" so it can be
 * retried later) -- we never fabricate a screening result.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../ai/ocr_helper.php';
require_once __DIR__ . '/../ai/ai_analysis_helper.php';

require_patient_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid request.');
    redirect('patient/reports.php');
}

$reportId = (int)($_POST['report_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM medical_reports WHERE id = :id AND patient_id = :pid");
$stmt->execute(['id' => $reportId, 'pid' => $_SESSION['patient_id']]);
$report = $stmt->fetch();

if (!$report) {
    set_flash('danger', 'Report not found.');
    redirect('patient/reports.php');
}

if (str_ends_with(strtolower($report['report_image']), '.pdf')) {
    set_flash('danger', 'AI screening currently supports image reports (JPG/PNG) only, not PDF.');
    redirect('patient/reports.php');
}

$imagePath = __DIR__ . '/../uploads/reports/' . $report['report_image'];

try {
    $extractedText = ocr_extract_text($imagePath);
    $result = ai_classify_report($extractedText);

    $aiResultText = "Classification: {$result['classification']}\n"
        . "Summary: {$result['summary']}\n\n"
        . "This is an automated screening only, not a medical diagnosis. Please discuss this report with your doctor.";

    $pdo->prepare("UPDATE medical_reports SET ai_result = :result WHERE id = :id")
        ->execute(['result' => $aiResultText, 'id' => $reportId]);

    try {
        create_notification(
            $pdo,
            $_SESSION['patient_id'],
            'Report Screened',
            'Your report has been screened: ' . $result['classification'] . '. Open Reports to see details.'
        );
    } catch (Throwable $e) {
        // best-effort only
    }

    set_flash('success', 'Report screened: ' . $result['classification'] . '.');
} catch (Throwable $e) {
    set_flash('danger', 'AI screening could not be completed: ' . $e->getMessage());
}

redirect('patient/reports.php');
