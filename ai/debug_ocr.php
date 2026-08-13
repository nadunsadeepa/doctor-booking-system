<?php
/**
 * ai/debug_ocr.php  (TEMPORARY DEBUG TOOL)
 * -----------------------------------------------------------
 * Checks that Tesseract is reachable from PHP, and lets you
 * run OCR on any already-uploaded report to see the raw
 * extracted text (and the exact exec() command used).
 *
 * Open in browser (must be logged in as admin):
 *   http://localhost/doctor/ai/debug_ocr.php
 *
 * SECURITY: delete this file once you're done debugging.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/ai_config.php';

require_admin_login();

// ---- Check 1: is exec() available? ----
$execAvailable = function_exists('exec');

// ---- Check 2: does Tesseract respond to --version? ----
$versionOutput = [];
$versionCode = null;
if ($execAvailable) {
    exec(escapeshellarg(TESSERACT_PATH) . ' --version 2>&1', $versionOutput, $versionCode);
}

$reports = $pdo->query(
    "SELECT id, patient_id, report_image, uploaded_at FROM medical_reports
     WHERE report_image NOT LIKE '%.pdf' ORDER BY uploaded_at DESC"
)->fetchAll();

$ocrText = null;
$ocrError = null;
$cmdUsed = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = (int)($_POST['report_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM medical_reports WHERE id = :id");
    $stmt->execute(['id' => $reportId]);
    $report = $stmt->fetch();

    if ($report) {
        $imagePath = __DIR__ . '/../uploads/reports/' . $report['report_image'];
        $cmdUsed = TESSERACT_PATH . ' "' . $imagePath . '" <tempfile> -l ' . TESSERACT_LANG;
        try {
            require_once __DIR__ . '/ocr_helper.php';
            $ocrText = ocr_extract_text($imagePath);
        } catch (Throwable $e) {
            $ocrError = $e->getMessage();
        }
    } else {
        $ocrError = 'Report not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OCR Debug Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:900px;">
    <h3>OCR Debug Tool (Tesseract)</h3>

    <div class="alert <?= $execAvailable ? 'alert-success' : 'alert-danger' ?>">
        <strong>PHP exec() available:</strong> <?= $execAvailable ? 'Yes' : 'NO -- ask your host to enable it, or move off shared hosting for this feature.' ?>
    </div>

    <?php if ($execAvailable): ?>
        <div class="alert <?= $versionCode === 0 ? 'alert-success' : 'alert-danger' ?>">
            <strong>Tesseract found at TESSERACT_PATH:</strong> <?= $versionCode === 0 ? 'Yes' : 'NO' ?><br>
            <strong>Path configured:</strong> <code><?= htmlspecialchars(TESSERACT_PATH) ?></code><br>
            <?php if ($versionCode !== 0): ?>
                <p class="mb-0 mt-2">Tesseract wasn't found at that path. Install it from
                    <a href="https://github.com/UB-Mannheim/tesseract/wiki" target="_blank">github.com/UB-Mannheim/tesseract/wiki</a>
                    (Windows) and update <code>TESSERACT_PATH</code> in <code>config/ai_config.php</code> to match
                    where it installed (check the installer's final screen, or search for <code>tesseract.exe</code>).</p>
            <?php else: ?>
                <pre class="mb-0 mt-2" style="font-size:0.8rem;"><?= htmlspecialchars(implode("\n", $versionOutput)) ?></pre>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <hr class="my-4">

    <h5>Test OCR on an uploaded report</h5>
    <form method="post" class="mb-4 d-flex gap-2">
        <select name="report_id" class="form-control" style="max-width:400px;">
            <?php foreach ($reports as $r): ?>
                <option value="<?= $r['id'] ?>">
                    #<?= $r['id'] ?> — patient <?= $r['patient_id'] ?> — <?= htmlspecialchars($r['report_image']) ?> (<?= htmlspecialchars($r['uploaded_at']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Run OCR</button>
    </form>

    <?php if ($ocrError): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($ocrError) ?></div>
    <?php endif; ?>

    <?php if ($ocrText): ?>
        <h6>Extracted Text (<?= strlen($ocrText) ?> chars)</h6>
        <pre style="background:#f5f5f5;padding:16px;border-radius:8px;max-height:400px;overflow:auto;"><?= htmlspecialchars($ocrText) ?></pre>
    <?php endif; ?>
</div>
</body>
</html>
