<?php
/**
 * Reports & Performance Analytics
 * Digital Marketing Campaign Tracker
 * 
 * Provides filterable marketing performance reports across channels and date ranges,
 * with aggregated spend, conversions, calculated ROI metrics, and one-click CSV export.
 */

$page_title = 'Reports & Analytics | AdTracker Pro';
$header_title = 'Performance Reports';
require_once __DIR__ . '/includes/header.php';

// Filter inputs
$channel   = trim($_GET['channel'] ?? '');
$status    = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');

// Build base query
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
        COALESCE(SUM(m.conversions), 0)  AS total_conversions,
        COALESCE(AVG(m.engagement_rate), 0) AS avg_engagement_rate
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
    $reportData = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database report error: " . htmlspecialchars($e->getMessage()));
}

// Compute Aggregate Totals for Report Footer
$sumBudget      = 0;
$sumSpend       = 0;
$sumImpressions = 0;
$sumClicks      = 0;
$sumConversions = 0;

foreach ($reportData as $row) {
    $sumBudget      += (float)$row['budget'];
    $sumSpend       += (float)$row['total_spend'];
    $sumImpressions += (int)$row['total_impressions'];
    $sumClicks      += (int)$row['total_clicks'];
    $sumConversions += (int)$row['total_conversions'];
}

$sumRemaining = $sumBudget - $sumSpend;
$overallCtr   = calculate_ctr($sumClicks, $sumImpressions);
$overallCvr   = calculate_conversion_rate($sumConversions, $sumClicks);

// Build export query string matching active filters
$exportParams = http_build_query([
    'channel'   => $channel,
    'status'    => $status,
    'date_from' => $date_from,
    'date_to'   => $date_to
]);
?>

<!-- ========================================================= -->
<!-- REPORT HEADER & EXPORT ACTION -->
<!-- ========================================================= -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Campaign Performance Report</h4>
        <p class="text-muted small mb-0">Cross-channel aggregated metrics, budget utilization, and ROI breakdown.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="export_csv.php?<?= $exportParams ?>" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-file-earmark-arrow-down"></i>
            <span>Export to CSV</span>
        </a>
    </div>
</div>

<!-- ========================================================= -->
<!-- REPORT FILTER TOOLBAR -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="reports.php" class="row g-2 align-items-end">
            <!-- Channel Filter -->
            <div class="col-sm-6 col-md-3">
                <label for="channel" class="form-label small fw-semibold text-muted mb-1">Channel</label>
                <select class="form-select form-select-sm" id="channel" name="channel">
                    <option value="">All Channels</option>
                    <option value="Social Media" <?= ($channel === 'Social Media') ? 'selected' : '' ?>>Social Media</option>
                    <option value="Email" <?= ($channel === 'Email') ? 'selected' : '' ?>>Email</option>
                    <option value="PPC" <?= ($channel === 'PPC') ? 'selected' : '' ?>>PPC (Search Ads)</option>
                    <option value="SEO" <?= ($channel === 'SEO') ? 'selected' : '' ?>>SEO</option>
                    <option value="Display" <?= ($channel === 'Display') ? 'selected' : '' ?>>Display Ads</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-sm-6 col-md-3">
                <label for="status" class="form-label small fw-semibold text-muted mb-1">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Active" <?= ($status === 'Active') ? 'selected' : '' ?>>Active</option>
                    <option value="Paused" <?= ($status === 'Paused') ? 'selected' : '' ?>>Paused</option>
                    <option value="Completed" <?= ($status === 'Completed') ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <!-- Date From -->
            <div class="col-sm-6 col-md-2">
                <label for="date_from" class="form-label small fw-semibold text-muted mb-1">Start Date From</label>
                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
            </div>

            <!-- Date To -->
            <div class="col-sm-6 col-md-2">
                <label for="date_to" class="form-label small fw-semibold text-muted mb-1">End Date To</label>
                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
            </div>

            <!-- Submit and Reset Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Apply
                </button>
                <?php if (!empty($channel) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                    <a href="reports.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================= -->
<!-- SUMMARY KPI CARDS -->
<!-- ========================================================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card primary h-100 p-3 shadow-sm border-0">
            <div class="stat-label">Total Allocated Budget</div>
            <div class="stat-value fs-4"><?= format_currency($sumBudget) ?></div>
            <div class="stat-subtext text-muted"><?= count($reportData) ?> Campaigns Included</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card warning h-100 p-3 shadow-sm border-0">
            <div class="stat-label">Total Actual Spend</div>
            <div class="stat-value fs-4 text-primary"><?= format_currency($sumSpend) ?></div>
            <div class="stat-subtext text-muted">Remaining: <?= format_currency($sumRemaining) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card info h-100 p-3 shadow-sm border-0">
            <div class="stat-label">Total Traffic</div>
            <div class="stat-value fs-4 text-dark"><?= number_format($sumClicks) ?></div>
            <div class="stat-subtext text-muted">CTR: <?= format_percent($overallCtr) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card success h-100 p-3 shadow-sm border-0">
            <div class="stat-label">Total Conversions</div>
            <div class="stat-value fs-4 text-success"><?= number_format($sumConversions) ?></div>
            <div class="stat-subtext text-muted">Avg Conv. Rate: <?= format_percent($overallCvr) ?></div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- PERFORMANCE DATA TABLE -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">
            <i class="bi bi-file-earmark-text text-primary me-2"></i> Detailed Performance Table
        </span>
        <span class="badge bg-light text-secondary border"><?= count($reportData) ?> Records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Campaign</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th class="text-end">Budget</th>
                        <th class="text-end">Total Spend</th>
                        <th class="text-end">Remaining</th>
                        <th class="text-end">Impressions</th>
                        <th class="text-end">Clicks</th>
                        <th class="text-end">CTR</th>
                        <th class="text-end">Conversions</th>
                        <th class="text-end">Conv. Rate</th>
                        <th class="text-end" title="Conversions per ₹100 spent">Conv / ₹100</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No campaign performance records found for selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): 
                            $b = (float)$row['budget'];
                            $s = (float)$row['total_spend'];
                            $rem = $b - $s;
                            $imp = (int)$row['total_impressions'];
                            $clk = (int)$row['total_clicks'];
                            $conv = (int)$row['total_conversions'];
                            $rowCtr = calculate_ctr($clk, $imp);
                            $rowCvr = calculate_conversion_rate($conv, $clk);
                            // Conversions per ₹100 spent (ROI indicator)
                            $convPer100 = ($s > 0) ? round(($conv / $s) * 100, 2) : 0.00;
                        ?>
                            <tr>
                                <td>
                                    <a href="campaign_detail.php?id=<?= $row['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= htmlspecialchars($row['campaign_name']) ?>
                                    </a>
                                </td>
                                <td><?= render_channel_badge($row['channel']) ?></td>
                                <td><?= render_status_badge($row['status']) ?></td>
                                <td class="text-end fw-semibold"><?= format_currency($b) ?></td>
                                <td class="text-end text-primary fw-semibold"><?= format_currency($s) ?></td>
                                <td class="text-end <?= $rem < 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= format_currency($rem) ?>
                                </td>
                                <td class="text-end"><?= number_format($imp) ?></td>
                                <td class="text-end"><?= number_format($clk) ?></td>
                                <td class="text-end"><span class="badge bg-light text-dark border"><?= format_percent($rowCtr) ?></span></td>
                                <td class="text-end text-success fw-bold"><?= number_format($conv) ?></td>
                                <td class="text-end"><span class="badge bg-light text-dark border"><?= format_percent($rowCvr) ?></span></td>
                                <td class="text-end fw-semibold text-primary"><?= number_format($convPer100, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <!-- Table Summary Footer -->
                <?php if (!empty($reportData)): ?>
                    <tfoot class="table-group-divider bg-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-uppercase text-muted" style="font-size: 0.8rem;">Totals / Averages</td>
                            <td class="text-end"><?= format_currency($sumBudget) ?></td>
                            <td class="text-end text-primary"><?= format_currency($sumSpend) ?></td>
                            <td class="text-end <?= $sumRemaining < 0 ? 'text-danger' : '' ?>"><?= format_currency($sumRemaining) ?></td>
                            <td class="text-end"><?= number_format($sumImpressions) ?></td>
                            <td class="text-end"><?= number_format($sumClicks) ?></td>
                            <td class="text-end"><span class="badge bg-white text-dark border"><?= format_percent($overallCtr) ?></span></td>
                            <td class="text-end text-success"><?= number_format($sumConversions) ?></td>
                            <td class="text-end"><span class="badge bg-white text-dark border"><?= format_percent($overallCvr) ?></span></td>
                            <td class="text-end text-muted">
                                <?= ($sumSpend > 0) ? number_format(($sumConversions / $sumSpend) * 100, 2) : '0.00' ?>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
