<?php
/**
 * Export Campaign Performance Report to CSV
 * Digital Marketing Campaign Tracker
 * 
 * Generates a downloadable CSV file representing the filtered campaign metrics.
 */

// Route protection
require_once __DIR__ . '/includes/auth_check.php';

// Filter inputs
$channel   = trim($_GET['channel'] ?? '');
$status    = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');

// Build query matching reports.php
$sql = "
    SELECT 
        c.id,
        c.campaign_name,
        c.channel,
        c.status,
        c.start_date,
        c.end_date,
        c.budget,
        COALESCE(SUM(m.amount_spent), 0) AS total_spend,
        COALESCE(SUM(m.impressions), 0)  AS total_impressions,
        COALESCE(SUM(m.clicks), 0)       AS total_clicks,
        COALESCE(SUM(m.conversions), 0)  AS total_conversions
    FROM campaigns c
    LEFT JOIN campaign_metrics m ON c.id = m.campaign_id
";

$where = ["1=1"];
$params = [];

if (!empty($channel)) {
    $where[] = "c.channel = :channel";
    $params[':channel'] = $channel;
}

if (!empty($status)) {
    $where[] = "c.status = :status";
    $params[':status'] = $status;
}

if (!empty($date_from)) {
    $where[] = "c.start_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "c.end_date <= :date_to";
    $params[':date_to'] = $date_to;
}

$sql .= " WHERE " . implode(" AND ", $where);
$sql .= " GROUP BY c.id ORDER BY total_conversions DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error exporting data: " . htmlspecialchars($e->getMessage()));
}

// Set CSV Response Headers
$filename = "campaign_performance_report_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Output UTF-8 BOM so Excel opens special characters correctly
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header Row
fputcsv($output, [
    'Campaign ID',
    'Campaign Name',
    'Channel',
    'Status',
    'Start Date',
    'End Date',
    'Budget (₹)',
    'Total Spend (₹)',
    'Remaining Balance (₹)',
    'Total Impressions',
    'Total Clicks',
    'CTR (%)',
    'Total Conversions',
    'Conversion Rate (%)',
    'Conversions per ₹100 Spent'
]);

// Write Data Rows
foreach ($rows as $r) {
    $budget = (float)$r['budget'];
    $spend  = (float)$r['total_spend'];
    $rem    = $budget - $spend;
    $imp    = (int)$r['total_impressions'];
    $clk    = (int)$r['total_clicks'];
    $conv   = (int)$r['total_conversions'];

    $ctr = ($imp > 0) ? round(($clk / $imp) * 100, 2) : 0.00;
    $cvr = ($clk > 0) ? round(($conv / $clk) * 100, 2) : 0.00;
    $roiRatio = ($spend > 0) ? round(($conv / $spend) * 100, 2) : 0.00;

    fputcsv($output, [
        $r['id'],
        $r['campaign_name'],
        $r['channel'],
        $r['status'],
        $r['start_date'],
        $r['end_date'],
        number_format($budget, 2, '.', ''),
        number_format($spend, 2, '.', ''),
        number_format($rem, 2, '.', ''),
        $imp,
        $clk,
        $ctr . '%',
        $conv,
        $cvr . '%',
        $roiRatio
    ]);
}

fclose($output);
exit();
