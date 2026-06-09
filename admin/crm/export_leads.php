<?php
// /admin/crm/export_leads.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

// ---------------------------------------------
// Inputs
// ---------------------------------------------
$format = strtolower(trim((string)($_POST['format'] ?? $_GET['format'] ?? 'csv')));
$range  = strtolower(trim((string)($_POST['range']  ?? $_GET['range']  ?? 'all')));

$search   = trim((string)($_POST['search']   ?? $_GET['search']   ?? ''));
$status   = trim((string)($_POST['status']   ?? $_GET['status']   ?? ''));
$stage_id = trim((string)($_POST['stage_id'] ?? $_GET['stage_id'] ?? ''));
$source   = trim((string)($_POST['source']   ?? $_GET['source']   ?? ''));

$allowedFormats = ['csv','excel','pdf'];
$allowedRanges  = ['all','month','week','today'];

if (!in_array($format, $allowedFormats, true)) $format = 'csv';
if (!in_array($range, $allowedRanges, true)) $range = 'all';

// ---------------------------------------------
// Date range filter (created_at)
// ---------------------------------------------
$dtFrom = null;
$dtTo   = null;

$now = new DateTime('now');
switch ($range) {
    case 'today':
        $dtFrom = (new DateTime('today'))->format('Y-m-d 00:00:00');
        $dtTo   = (new DateTime('today'))->format('Y-m-d 23:59:59');
        break;

    case 'week':
        // Monday start
        $start = new DateTime('monday this week');
        $end   = new DateTime('sunday this week');
        $dtFrom = $start->format('Y-m-d 00:00:00');
        $dtTo   = $end->format('Y-m-d 23:59:59');
        break;

    case 'month':
        $start = new DateTime(date('Y-m-01'));
        $end   = new DateTime(date('Y-m-t'));
        $dtFrom = $start->format('Y-m-d 00:00:00');
        $dtTo   = $end->format('Y-m-d 23:59:59');
        break;

    case 'all':
    default:
        // no date filter
        break;
}

// ---------------------------------------------
// Build query (same logic style as leads.php)
// ---------------------------------------------
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = "(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($status !== '') {
    $conditions[] = "l.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($stage_id !== '' && is_numeric($stage_id)) {
    $conditions[] = "l.stage_id = ?";
    $params[] = (int)$stage_id;
    $types .= 'i';
}

if ($source !== '') {
    $conditions[] = "l.source = ?";
    $params[] = $source;
    $types .= 's';
}

if ($dtFrom !== null && $dtTo !== null) {
    $conditions[] = "(l.created_at BETWEEN ? AND ?)";
    $params[] = $dtFrom;
    $params[] = $dtTo;
    $types .= 'ss';
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Pull leads + stage name
$sql = "
    SELECT
        l.id,
        l.name,
        l.email,
        l.phone,
        l.status,
        l.source,
        l.stage_id,
        s.name AS stage_name,
        l.created_at
    FROM crm_leads l
    LEFT JOIN crm_stages s ON l.stage_id = s.id
    $where
    ORDER BY l.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo "Export error: cannot prepare query.";
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// ---------------------------------------------
// Helpers
// ---------------------------------------------
function ps_safe_filename(string $name): string {
    $name = preg_replace('/[^a-zA-Z0-9\-_\.]+/', '_', $name);
    return trim($name, '_');
}

function ps_csv_escape($value): string {
    $v = (string)$value;
    // Normalize newlines
    $v = str_replace(["\r\n","\r"], "\n", $v);
    // Wrap in quotes if needed
    if (strpbrk($v, "\"\n,") !== false) {
        $v = '"' . str_replace('"', '""', $v) . '"';
    }
    return $v;
}

$stamp = date('Y-m-d_His');
$baseName = ps_safe_filename("leads_export_{$range}_{$stamp}");

// ---------------------------------------------
// Export: CSV
// ---------------------------------------------
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$baseName}.csv\"");
    header('Pragma: no-cache');
    header('Expires: 0');

    // UTF-8 BOM for Excel friendliness
    echo "\xEF\xBB\xBF";

    $headers = ['ID','Name','Email','Phone','Status','Stage','Source','Created At'];
    echo implode(',', array_map('ps_csv_escape', $headers)) . "\n";

    foreach ($rows as $r) {
        $line = [
            $r['id'] ?? '',
            $r['name'] ?? '',
            $r['email'] ?? '',
            $r['phone'] ?? '',
            $r['status'] ?? '',
            ($r['stage_name'] ?? ''),
            $r['source'] ?? '',
            $r['created_at'] ?? '',
        ];
        echo implode(',', array_map('ps_csv_escape', $line)) . "\n";
    }
    exit;
}

// ---------------------------------------------
// Export: Excel (simple HTML table .xls)
// Works in Excel/Sheets without extra libraries
// ---------------------------------------------
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$baseName}.xls\"");
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // BOM

    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Leads Export</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
            th { background: #f3f3f3; text-align: left; }
        </style>
    </head>
    <body>
        <h3>Leads Export (<?= htmlspecialchars(strtoupper($range)) ?>)</h3>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Stage</th>
                <th>Source</th>
                <th>Created At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)($r['id'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['phone'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['status'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['stage_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['source'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// ---------------------------------------------
// Export: PDF (Dompdf if available, else HTML fallback)
// ---------------------------------------------
if ($format === 'pdf') {

    // Build HTML (used by Dompdf or fallback)
    ob_start();
    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Leads Export</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
            h2 { margin: 0 0 10px; }
            .meta { margin-bottom: 12px; color: #444; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
            th { background: #f3f3f3; text-align: left; }
        </style>
    </head>
    <body>
        <h2>Leads Export (<?= htmlspecialchars(strtoupper($range)) ?>)</h2>
        <div class="meta">
            Generated: <?= htmlspecialchars(date('M j, Y g:i A')) ?><br>
            Total leads: <?= (int)count($rows) ?>
        </div>

        <table>
            <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>Name</th>
                <th>Email</th>
                <th style="width:110px;">Phone</th>
                <th style="width:90px;">Status</th>
                <th style="width:120px;">Stage</th>
                <th>Source</th>
                <th style="width:140px;">Created</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int)($r['id'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['phone'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['status'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['stage_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['source'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // If Dompdf exists, generate a real PDF
    if (class_exists('\Dompdf\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"{$baseName}.pdf\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $dompdf->output();
        exit;
    }

    // Fallback: download HTML (print to PDF in browser)
    header('Content-Type: text/html; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$baseName}.html\"");
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $html;
    exit;
}

// Default fallback
http_response_code(400);
echo "Invalid export format.";
exit;
