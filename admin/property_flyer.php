<?php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

require_login();

$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
if ($pid <= 0) { http_response_code(400); exit("Invalid Property ID"); }

// TCPDF (drop-in)
require_once __DIR__ . "/tcpdf/tcpdf.php";

/**
 * Helpers
 */
function fix_mojibake(string $s): string {
    // Common broken UTF-8 sequences seen from copy/paste/encoding mismatch
    $s = str_replace(
        ["â€¢", "â•", "â–", "Â", "â", "â€", "â€™", "â€œ", "â€", "â€“", "â€”"],
        ["•",  "•",  "-",  "",  "",   "",  "'",   '"',   '"',   "-",   "-"],
        $s
    );

    // Also remove any remaining stray control chars (including 0x81-like)
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $s) ?? $s;

    return $s;
}

function safe_text($str): string {
    $str = (string)$str;
    $str = str_replace("&nbsp;", " ", $str);
    $str = fix_mojibake($str);
    return trim($str);
}

// allow basic HTML tags for TCPDF rendering
function clean_html_for_pdf($html): string {
    $html = (string)$html;

    $html = str_replace("&nbsp;", " ", $html);
    $html = fix_mojibake($html);

    // Remove scripts/styles
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

    // Remove bootstrap/grid div classes that can mess layout
    $html = preg_replace('/<div[^>]*class="[^"]*(col-|row)[^"]*"[^>]*>/i', '<div>', $html);

    // Keep only safe tags TCPDF handles well
    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><span><div>';
    $html = strip_tags($html, $allowed);

    // Make lists nicer
    $html = preg_replace('/<ul[^>]*>/', '<ul style="margin:0;padding-left:16px;">', $html);
    $html = preg_replace('/<ol[^>]*>/', '<ol style="margin:0;padding-left:16px;">', $html);
    $html = preg_replace('/<li[^>]*>/', '<li style="margin-bottom:4px;">', $html);

    // Ensure paragraphs have spacing
    $html = preg_replace('/<p[^>]*>/', '<p style="margin:0 0 6px 0;">', $html);

    return trim($html);
}

function table_exists(mysqli $con, string $table): bool {
    $tableEsc = $con->real_escape_string($table);
    $dbRes = $con->query("SELECT DATABASE() AS db");
    $dbRow = $dbRes ? $dbRes->fetch_assoc() : null;
    $db = $dbRow["db"] ?? "";
    if ($db === "") return false;

    $dbEsc = $con->real_escape_string($db);
    $q = $con->query("SELECT 1 FROM information_schema.tables WHERE table_schema='{$dbEsc}' AND table_name='{$tableEsc}' LIMIT 1");
    return ($q && $q->num_rows > 0);
}

/**
 * --- Get site currency + logo ---
 * supports: site_info or siteinfo
 */
$siteCurrency = "₦";

// Default logo fallback (if DB is empty)
$defaultLogo = __DIR__ . "/../images/Minimalist House Line Real Estate Logo - 1.png";
$siteLogoPath = is_file($defaultLogo) ? $defaultLogo : "";

// company name on flyer
$companyName = "Property Flyer";

// Detect which table exists
$siteTable = null;
if (table_exists($con, "site_info")) $siteTable = "site_info";
elseif (table_exists($con, "siteinfo")) $siteTable = "siteinfo";

if ($siteTable) {
    $siteRes = $con->query("SELECT logo_path, currency FROM {$siteTable} ORDER BY id DESC LIMIT 1");
    if ($siteRes && $siteRes->num_rows > 0) {
        $s = $siteRes->fetch_assoc();

        if (!empty($s["currency"])) {
            $siteCurrency = safe_text($s["currency"]);
        }

        /**
         * LOGO PATH FIX
         * logo_path may be:
         * - images/logo.png
         * - /images/logo.png
         * - Minimalist House....png (filename only)
         */
        if (!empty($s["logo_path"])) {
            $lp = trim((string)$s["logo_path"]);
            $lp = str_replace("\\", "/", $lp);

            // If only filename is stored, assume /images/
            if ($lp !== "" && strpos($lp, "/") === false) {
                $lp = "images/" . $lp;
            }

            $lp = ltrim($lp, "/");
            $candidate = __DIR__ . "/../" . $lp; // /admin -> project root

            if (is_file($candidate)) {
                $siteLogoPath = $candidate;
            }
        }
    }
}

/**
 * --- Fetch property ---
 */
$stmt = $con->prepare("SELECT * FROM property WHERE pid = ? LIMIT 1");
$stmt->bind_param("i", $pid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) { http_response_code(404); exit("Property not found"); }

/**
 * --- Property images ---
 * detect images in:
 * 1) admin/property/filename
 * 2) ../property/filename
 */
$imageCols = ["pimage","pimage1","pimage2","pimage3","pimage4"];
$imgs = [];

foreach ($imageCols as $c) {
    $f = trim((string)($row[$c] ?? ""));
    if ($f === "") continue;

    $f = ltrim($f, "/");

    $p1 = __DIR__ . "/property/" . $f;       // admin/property/
    $p2 = __DIR__ . "/../property/" . $f;    // root/property/

    if (is_file($p1)) $imgs[] = $p1;
    elseif (is_file($p2)) $imgs[] = $p2;
}

$cover = $imgs[0] ?? "";

/**
 * --- Format fields ---
 */
$title = safe_text($row["title"] ?? "");
$type  = safe_text($row["type"] ?? "");
$stype = safe_text($row["stype"] ?? "");
$city  = safe_text($row["city"] ?? "");
$loc   = safe_text($row["location"] ?? "");
$state = safe_text($row["state"] ?? "");

$status = strtoupper(trim((string)$row["status"]));
$status = safe_text($status);
if ($status === "") $status = "AVAILABLE";

$priceText = "Price on Request";
if ($row["price"] !== null && $row["price"] !== "") {
    $priceText = $siteCurrency . number_format((float)$row["price"], 0);
}

$descHtml = clean_html_for_pdf($row["pcontent"] ?? "");
$featHtml = clean_html_for_pdf($row["feature"] ?? "");

/**
 * --- TCPDF setup ---
 */
$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
$pdf->SetCreator("PropertySync");
$pdf->SetAuthor("Admin");
$pdf->SetTitle("Property Flyer - PID {$pid}");
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();

// ✅ Unicode font so ₦ renders
$pdf->SetFont('dejavusans', '', 10);

/**
 * ---------- Header ----------
 */
$headerHtml = '
<table cellpadding="0" cellspacing="0" style="width:100%;">
  <tr>
    <td style="width:22%; vertical-align:middle;">'.(
        $siteLogoPath ? '<img src="'.$siteLogoPath.'" style="width:110px;">' : ''
    ).'</td>
    <td style="width:56%; vertical-align:middle;">
      <div style="font-size:18px; font-weight:bold; color:#0b1220;">'.$companyName.'</div>
      <div style="font-size:10px; color:#6b7280;">Premium Property Flyer • Print Ready</div>
    </td>
    <td style="width:22%; text-align:right; vertical-align:middle;">
      <div style="font-size:10px; color:#6b7280;">Ref</div>
      <div style="font-size:12px; font-weight:bold; color:#0b1220;">PID: '.$pid.'</div>
    </td>
  </tr>
</table>
<div style="height:10px;"></div>
';
$pdf->writeHTML($headerHtml, true, false, true, false, '');

/**
 * ---------- HERO IMAGE ----------
 */
if ($cover) {
    $pdf->SetFillColor(245,245,245);
    $yHero = $pdf->GetY();
    $pdf->RoundedRect(12, $yHero, 186, 85, 4, '1111', 'F');
    $pdf->Image($cover, 12, $yHero, 186, 85, "", "", "", true, 150, "", false, false, 0, "CM", false, false);
    $pdf->SetY($yHero + 89);
} else {
    $pdf->SetFillColor(243,244,246);
    $yHero = $pdf->GetY();
    $pdf->RoundedRect(12, $yHero, 186, 85, 4, '1111', 'F');
    $pdf->SetY($yHero + 89);
}

/**
 * ---------- TITLE + PRICE + TAGS ----------
 */
$topInfo = '
<table cellpadding="0" cellspacing="0" style="width:100%;">
  <tr>
    <td style="width:70%; vertical-align:top;">
      <div style="font-size:20px; font-weight:bold; color:#111827; line-height:1.2;">'.$title.'</div>
      <div style="font-size:10px; color:#6b7280; margin-top:4px;">'.$loc.', '.$city.', '.$state.'</div>
    </td>
    <td style="width:30%; vertical-align:top; text-align:right;">
      <div style="font-size:10px; color:#6b7280;">Price</div>
      <div style="font-size:20px; font-weight:bold; color:#0b1220;">'.$priceText.'</div>
      <div style="margin-top:4px; font-size:10px; color:#6b7280;">Status: <b>'.$status.'</b></div>
    </td>
  </tr>
</table>

<div style="height:10px;"></div>

<table cellpadding="7" cellspacing="0" style="width:100%;">
  <tr>
    <td style="background:#f3f4f6; border:1px solid #e5e7eb; border-radius:10px; font-size:10px;">
      <b>Type:</b> '.$type.'
    </td>
    <td style="background:#f3f4f6; border:1px solid #e5e7eb; border-radius:10px; font-size:10px;">
      <b>For:</b> '.$stype.'
    </td>
    <td style="background:#f3f4f6; border:1px solid #e5e7eb; border-radius:10px; font-size:10px;">
      <b>Bedrooms:</b> '.(int)$row["bedroom"].'
    </td>
    <td style="background:#f3f4f6; border:1px solid #e5e7eb; border-radius:10px; font-size:10px;">
      <b>Bathrooms:</b> '.(int)$row["bathroom"].'
    </td>
  </tr>
</table>

<div style="height:12px;"></div>
';
$pdf->writeHTML($topInfo, true, false, true, false, '');

/**
 * ---------- DESCRIPTION + DETAILS ----------
 * ✅ Removed "Views" from details table
 */
$detailsHtml = '
<table cellpadding="0" cellspacing="0" style="width:100%;">
  <tr>
    <td style="width:62%; vertical-align:top; padding-right:10px;">
      <div style="border:1px solid #e5e7eb; border-radius:14px; padding:12px;">
        <div style="font-size:11px; font-weight:bold; color:#0b1220; letter-spacing:0.6px;">DESCRIPTION</div>
        <div style="height:6px;"></div>
        <div style="font-size:10px; color:#111827; line-height:1.65;">'.$descHtml.'</div>
      </div>
    </td>
    <td style="width:38%; vertical-align:top;">
      <div style="border:1px solid #e5e7eb; border-radius:14px; padding:12px;">
        <div style="font-size:11px; font-weight:bold; color:#0b1220; letter-spacing:0.6px;">PROPERTY DETAILS</div>
        <div style="height:6px;"></div>
        <table cellpadding="6" cellspacing="0" style="width:100%; font-size:10px;">
          <tr><td style="color:#6b7280;">Toilets</td><td style="text-align:right;"><b>'.(int)$row["toilet"].'</b></td></tr>
          <tr><td style="color:#6b7280;">Kitchen</td><td style="text-align:right;"><b>'.(int)$row["kitchen"].'</b></td></tr>
          <tr><td style="color:#6b7280;">Balcony</td><td style="text-align:right;"><b>'.(int)$row["balcony"].'</b></td></tr>
          <tr><td style="color:#6b7280;">Size</td><td style="text-align:right;"><b>'.safe_text($row["size"]).'</b></td></tr>
          <tr><td style="color:#6b7280;">Status</td><td style="text-align:right;"><b>'.$status.'</b></td></tr>
        </table>
      </div>

      <div style="height:10px;"></div>

      <div style="border:1px solid #e5e7eb; border-radius:14px; padding:12px;">
        <div style="font-size:11px; font-weight:bold; color:#0b1220; letter-spacing:0.6px;">FEATURES</div>
        <div style="height:6px;"></div>
        <div style="font-size:10px; color:#111827; line-height:1.65;">'.$featHtml.'</div>
      </div>
    </td>
  </tr>
</table>

<div style="height:12px;"></div>
';
$pdf->writeHTML($detailsHtml, true, false, true, false, '');

/**
 * ---------- GALLERY ----------
 */
if (!empty($imgs)) {
    $pdf->writeHTML('<div style="font-size:11px; font-weight:bold; color:#0b1220; letter-spacing:0.6px; margin-bottom:6px;">GALLERY</div>', true, false, true, false, '');

    $thumbW = 61;
    $thumbH = 48;
    $gap = 3;

    $x = 12;
    $y = $pdf->GetY();
    $col = 0;

    foreach ($imgs as $img) {
        if ($y + $thumbH > 285) {
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', '', 10);
            $x = 12;
            $y = 12;
            $col = 0;
        }

        $pdf->SetFillColor(245,245,245);
        $pdf->RoundedRect($x, $y, $thumbW, $thumbH, 3, '1111', 'F');

        $pdf->Image($img, $x, $y, $thumbW, $thumbH, "", "", "", true, 150, "", false, false, 0, "CM", false, false);

        $col++;
        if ($col >= 3) {
            $col = 0;
            $x = 12;
            $y += $thumbH + $gap;
        } else {
            $x += $thumbW + $gap;
        }
    }

    $pdf->SetY($y + $thumbH + 4);
}

/**
 * ---------- Footer ----------
 */
$pdf->SetY(-18);
$pdf->SetFont('dejavusans', '', 9);
$pdf->SetTextColor(90,90,90);
$pdf->Cell(186, 8, "Generated on ".date("Y-m-d H:i")." | PropertySync Flyer", 0, 0, "C");

$pdf->Output("property-flyer-{$pid}.pdf", "D");
exit;
