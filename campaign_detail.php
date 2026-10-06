<?php
/**
 * Single Campaign Detail & Analytics Page
 * Digital Marketing Campaign Tracker
 * 
 * Displays comprehensive campaign information, aggregate KPI summary cards,
 * interactive Chart.js line chart for clicks & conversions over time,
 * and complete metrics history table.
 */

$campaign_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$campaign_id) {
    header('Location: campaigns.php');
    exit();
}

$page_title = 'Campaign Analytics | AdTracker Pro';
$header_title = 'Campaign Analytics';
require_once __DIR__ . '/includes/header.php';

try {
    // 1. Fetch Campaign Information
    $stmt = $pdo->prepare("
        SELECT c.*, u.name AS owner_name
        FROM campaigns c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $campaign_id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        set_flash_message('danger', 'Campaign not found.');
        header('Location: campaigns.php');
        exit();
    }

    // 2. Fetch Aggregate Metrics for this Campaign
    $stmtAgg = $pdo->prepare("
        SELECT 
            COUNT(*)                        AS total_log_entries,
            COALESCE(SUM(amount_spent), 0)  AS total_spend,
            COALESCE(SUM(impressions), 0)   AS total_impressions,
            COALESCE(SUM(clicks), 0)        AS total_clicks,
            COALESCE(SUM(conversions), 0)   AS total_conversions,
            COALESCE(AVG(engagement_rate), 0) AS avg_engagement_rate
        FROM campaign_metrics
        WHERE campaign_id = :campaign_id
    ");
    $stmtAgg->execute([':campaign_id' => $campaign_id]);
    $agg = $stmtAgg->fetch();

    $budget = (float)$campaign['budget'];
    $total_spend = (float)$agg['total_spend'];
    $remaining_budget = $budget - $total_spend;
    $budget_pct = ($budget > 0) ? min(100, round(($total_spend / $budget) * 100, 1)) : 0;

    $total_impressions = (int)$agg['total_impressions'];
    $total_clicks = (int)$agg['total_clicks'];
    $total_conversions = (int)$agg['total_conversions'];
    $avg_engagement = (float)$agg['avg_engagement_rate'];

    // KPI Calculations
    $ctr = calculate_ctr($total_clicks, $total_impressions);
    $cvr = calculate_conversion_rate($total_conversions, $total_clicks);
    $cpc = ($total_clicks > 0) ? round($total_spend / $total_clicks, 2) : 0.00;
    $cpa = ($total_conversions > 0) ? round($total_spend / $total_conversions, 2) : 0.00;

    // 3. Fetch Chronological Metrics for Chart.js Trend Line
    $stmtHistory = $pdo->prepare("
        SELECT *
        FROM campaign_metrics
        WHERE campaign_id = :campaign_id
        ORDER BY date_recorded ASC
    ");
    $stmtHistory->execute([':campaign_id' => $campaign_id]);
    $metricsList = $stmtHistory->fetchAll();

    // Prepare Chart Data
    $chartDates = [];
    $chartClicks = [];
    $chartConversions = [];
    $chartSpend = [];

    foreach ($metricsList as $m) {
        $chartDates[] = date('M d', strtotime($m['date_recorded']));
        $chartClicks[] = (int)$m['clicks'];
        $chartConversions[] = (int)$m['conversions'];
        $chartSpend[] = (float)$m['amount_spent'];
    }

} catch (PDOException $e) {
    die("Error loading campaign details: " . htmlspecialchars($e->getMessage()));
}
?>

<!-- ========================================================= -->
<!-- BREADCRUMB & HEADER ACTIONS -->
<!-- ========================================================= -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="campaigns.php" class="text-decoration-none">Campaigns</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($campaign['campaign_name']) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($campaign['campaign_name']) ?></h4>
            <?= render_status_badge($campaign['status']) ?>
            <?= render_channel_badge($campaign['channel']) ?>
        </div>
        <small class="text-muted">
            <i class="bi bi-calendar3 me-1"></i> Timeline: <?= date('M d, Y', strtotime($campaign['start_date'])) ?> to <?= date('M d, Y', strtotime($campaign['end_date'])) ?>
            &bull; Managed by <?= htmlspecialchars($campaign['owner_name'] ?? 'Marketer') ?>
        </small>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex gap-2">
        <a href="add_metrics.php?campaign_id=<?= $campaign['id'] ?>" class="btn btn-sm btn-success">
            <i class="bi bi-plus-circle me-1"></i> Log Metrics
        </a>
        <a href="edit_campaign.php?id=<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <button type="button" 
                class="btn btn-sm btn-outline-danger" 
                data-bs-toggle="modal" 
                data-bs-target="#deleteCampaignModal"
                data-campaign-id="<?= $campaign['id'] ?>"
                data-campaign-name="<?= htmlspecialchars($campaign['campaign_name']) ?>">
            <i class="bi bi-trash me-1"></i> Delete
        </button>
    </div>
</div>

<!-- ========================================================= -->
<!-- BUDGET & OVERVIEW PROGRESS -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <div class="row align-items-center">
            <div class="col-md-3 border-end">
                <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.72rem;">Allocated Budget</small>
                <h5 class="fw-bold mb-0 text-dark"><?= format_currency($budget) ?></h5>
            </div>
            <div class="col-md-3 border-end">
                <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.72rem;">Total Spend to Date</small>
                <h5 class="fw-bold mb-0 text-primary"><?= format_currency($total_spend) ?></h5>
            </div>
            <div class="col-md-3 border-end">
                <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.72rem;">Remaining Balance</small>
                <h5 class="fw-bold mb-0 <?= $remaining_budget < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= format_currency($remaining_budget) ?>
                </h5>
            </div>
            <div class="col-md-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Budget Utilized</span>
                    <span class="fw-bold"><?= $budget_pct ?>%</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar <?= $budget_pct > 90 ? 'bg-danger' : ($budget_pct > 75 ? 'bg-warning' : 'bg-primary') ?>" 
                         role="progressbar" 
                         style="width: <?= $budget_pct ?>%;" 
                         aria-valuenow="<?= $budget_pct ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- PERFORMANCE KPI METRICS ROW -->
<!-- ========================================================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card primary h-100 shadow-sm border-0 p-3">
            <div class="stat-label">Total Impressions</div>
            <div class="stat-value fs-3"><?= number_format($total_impressions) ?></div>
            <div class="stat-subtext text-muted">Audience Reach</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card stat-card info h-100 shadow-sm border-0 p-3">
            <div class="stat-label">Total Clicks & CTR</div>
            <div class="stat-value fs-3 text-primary"><?= number_format($total_clicks) ?></div>
            <div class="stat-subtext text-muted">CTR: <strong><?= format_percent($ctr) ?></strong></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card stat-card success h-100 shadow-sm border-0 p-3">
            <div class="stat-label">Total Conversions</div>
            <div class="stat-value fs-3 text-success"><?= number_format($total_conversions) ?></div>
            <div class="stat-subtext text-muted">Conversion Rate: <strong><?= format_percent($cvr) ?></strong></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card stat-card warning h-100 shadow-sm border-0 p-3">
            <div class="stat-label">Avg CPC / CPA</div>
            <div class="stat-value fs-3 text-dark"><?= format_currency($cpc) ?></div>
            <div class="stat-subtext text-muted">Cost Per Acquisition: <strong><?= format_currency($cpa) ?></strong></div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- LINE CHART: CLICKS & CONVERSIONS OVER TIME -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <span class="fw-bold text-dark">
            <i class="bi bi-graph-up text-primary me-2"></i> Performance Trend (Clicks &amp; Conversions Over Time)
        </span>
        <span class="badge bg-light text-muted border"><?= count($metricsList) ?> Logged Entries</span>
    </div>
    <div class="card-body">
        <?php if (empty($metricsList)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bar-chart-line fs-1 d-block mb-2"></i>
                <p>No daily metrics have been logged for this campaign yet.</p>
                <a href="add_metrics.php?campaign_id=<?= $campaign['id'] ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Log First Metric Entry
                </a>
            </div>
        <?php else: ?>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="campaignTrendChart"></canvas>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================= -->
<!-- METRICS HISTORY TABLE -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <span class="fw-bold text-dark">
            <i class="bi bi-table text-secondary me-2"></i> Daily Performance Log
        </span>
        <a href="add_metrics.php?campaign_id=<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-plus-lg me-1"></i> Add Entry
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date Recorded</th>
                        <th class="text-end">Impressions</th>
                        <th class="text-end">Clicks</th>
                        <th class="text-end">CTR</th>
                        <th class="text-end">Conversions</th>
                        <th class="text-end">Conv. Rate</th>
                        <th class="text-end">Amount Spent</th>
                        <th class="text-end">Engagement Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($metricsList)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No entries logged yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        // Reverse chronological order for table display
                        $reversedList = array_reverse($metricsList);
                        foreach ($reversedList as $m): 
                            $rowImpressions = (int)$m['impressions'];
                            $rowClicks      = (int)$m['clicks'];
                            $rowConversions = (int)$m['conversions'];
                            $rowSpend       = (float)$m['amount_spent'];
                            $rowCtr         = calculate_ctr($rowClicks, $rowImpressions);
                            $rowCvr         = calculate_conversion_rate($rowConversions, $rowClicks);
                        ?>
                            <tr>
                                <td class="fw-semibold">
                                    <i class="bi bi-calendar-event text-muted me-1"></i>
                                    <?= date('M d, Y', strtotime($m['date_recorded'])) ?>
                                </td>
                                <td class="text-end"><?= number_format($rowImpressions) ?></td>
                                <td class="text-end text-primary fw-semibold"><?= number_format($rowClicks) ?></td>
                                <td class="text-end"><span class="badge bg-light text-dark border"><?= format_percent($rowCtr) ?></span></td>
                                <td class="text-end text-success fw-semibold"><?= number_format($rowConversions) ?></td>
                                <td class="text-end"><span class="badge bg-light text-dark border"><?= format_percent($rowCvr) ?></span></td>
                                <td class="text-end fw-semibold"><?= format_currency($rowSpend) ?></td>
                                <td class="text-end text-muted"><?= format_percent($m['engagement_rate']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- DELETE MODAL -->
<!-- ========================================================= -->
<div class="modal fade" id="deleteCampaignModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title text-danger fw-bold" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="delete_campaign.php" method="POST">
                <div class="modal-body py-3">
                    <p>Are you sure you want to permanently delete <strong id="deleteModalCampaignName"><?= htmlspecialchars($campaign['campaign_name']) ?></strong>?</p>
                    <div class="alert alert-warning small mb-0">
                        All logged daily metrics and performance history will be permanently erased.
                    </div>
                    <input type="hidden" name="campaign_id" id="deleteModalCampaignId" value="<?= $campaign['id'] ?>">
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js Trend Line Initialization -->
<?php if (!empty($metricsList)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctxTrend = document.getElementById('campaignTrendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartDates) ?>,
                datasets: [
                    {
                        label: 'Clicks',
                        data: <?= json_encode($chartClicks) ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Conversions',
                        data: <?= json_encode($chartConversions) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Amount Spent (₹)',
                        data: <?= json_encode($chartSpend) ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.2,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Inter' }, boxWidth: 14 }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: { display: true, text: 'Clicks & Conversions', font: { family: 'Inter', size: 12 } },
                        grid: { color: '#f1f5f9' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Spend (₹)', font: { family: 'Inter', size: 12 } },
                        ticks: {
                            callback: function(val) { return '₹' + val; }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
