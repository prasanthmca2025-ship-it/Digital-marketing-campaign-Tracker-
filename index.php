<?php
/**
 * Dashboard (Home Page)
 * Digital Marketing Campaign Tracker
 * 
 * Displays key campaign metrics, summary cards, spend comparison bar chart,
 * channel distribution pie chart, and recent campaign activities.
 */

$page_title = 'Dashboard | AdTracker Pro';
$header_title = 'Marketing Dashboard';
require_once __DIR__ . '/includes/header.php';

try {
    // -------------------------------------------------------------
    // 1. Fetch Key Summary Statistics
    // -------------------------------------------------------------
    // Total Campaigns & Active Campaigns
    $stmtSummary = $pdo->query("
        SELECT 
            COUNT(*) AS total_campaigns,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_campaigns,
            SUM(CASE WHEN status = 'Paused' THEN 1 ELSE 0 END) AS paused_campaigns,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_campaigns,
            COALESCE(SUM(budget), 0) AS total_budget
        FROM campaigns
    ");
    $summary = $stmtSummary->fetch();

    $total_campaigns    = (int)($summary['total_campaigns'] ?? 0);
    $active_campaigns   = (int)($summary['active_campaigns'] ?? 0);
    $paused_campaigns   = (int)($summary['paused_campaigns'] ?? 0);
    $completed_campaigns= (int)($summary['completed_campaigns'] ?? 0);
    $total_budget       = (float)($summary['total_budget'] ?? 0);

    // Total Spend, Impressions, Clicks, Conversions across all campaigns
    $stmtMetrics = $pdo->query("
        SELECT 
            COALESCE(SUM(amount_spent), 0) AS total_spend,
            COALESCE(SUM(impressions), 0)  AS total_impressions,
            COALESCE(SUM(clicks), 0)       AS total_clicks,
            COALESCE(SUM(conversions), 0)  AS total_conversions
        FROM campaign_metrics
    ");
    $metrics = $stmtMetrics->fetch();

    $total_spend       = (float)($metrics['total_spend'] ?? 0);
    $total_impressions = (int)($metrics['total_impressions'] ?? 0);
    $total_clicks      = (int)($metrics['total_clicks'] ?? 0);
    $total_conversions = (int)($metrics['total_conversions'] ?? 0);

    // Calculated overall CTR and CVR
    $overall_ctr = calculate_ctr($total_clicks, $total_impressions);
    $overall_cvr = calculate_conversion_rate($total_conversions, $total_clicks);
    $budget_remaining = max(0, $total_budget - $total_spend);

    // -------------------------------------------------------------
    // 2. Data for Spend Comparison Bar Chart (Budget vs Spend per Campaign)
    // -------------------------------------------------------------
    $stmtSpend = $pdo->query("
        SELECT 
            c.id,
            c.campaign_name,
            c.budget,
            COALESCE(SUM(m.amount_spent), 0) AS actual_spend
        FROM campaigns c
        LEFT JOIN campaign_metrics m ON c.id = m.campaign_id
        GROUP BY c.id, c.campaign_name, c.budget
        ORDER BY c.id ASC
    ");
    $spendData = $stmtSpend->fetchAll();

    $chartCampaignLabels = [];
    $chartBudgets = [];
    $chartSpends = [];

    foreach ($spendData as $row) {
        // Truncate name if too long for clean chart display
        $shortName = strlen($row['campaign_name']) > 22 
            ? substr($row['campaign_name'], 0, 19) . '...' 
            : $row['campaign_name'];
        $chartCampaignLabels[] = $shortName;
        $chartBudgets[] = (float)$row['budget'];
        $chartSpends[] = (float)$row['actual_spend'];
    }

    // -------------------------------------------------------------
    // 3. Data for Channel Distribution Pie/Doughnut Chart
    // -------------------------------------------------------------
    $stmtChannels = $pdo->query("
        SELECT 
            channel,
            COUNT(*) AS campaign_count,
            COALESCE(SUM(budget), 0) AS channel_budget
        FROM campaigns
        GROUP BY channel
        ORDER BY campaign_count DESC
    ");
    $channelData = $stmtChannels->fetchAll();

    $chartChannelLabels = [];
    $chartChannelCounts = [];
    foreach ($channelData as $row) {
        $chartChannelLabels[] = $row['channel'];
        $chartChannelCounts[] = (int)$row['campaign_count'];
    }

    // -------------------------------------------------------------
    // 4. Recent Campaigns (Last 5)
    // -------------------------------------------------------------
    $stmtRecent = $pdo->query("
        SELECT 
            c.*,
            COALESCE(SUM(m.amount_spent), 0) AS total_spent,
            COALESCE(SUM(m.conversions), 0)  AS total_conversions,
            COALESCE(SUM(m.clicks), 0)       AS total_clicks
        FROM campaigns c
        LEFT JOIN campaign_metrics m ON c.id = m.campaign_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $recentCampaigns = $stmtRecent->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . htmlspecialchars($e->getMessage()));
}
?>

<!-- ========================================================= -->
<!-- SUMMARY KPI CARDS -->
<!-- ========================================================= -->
<div class="row g-3 mb-4">
    <!-- Total Campaigns -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card primary h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="stat-label">Total Campaigns</div>
                    <div class="stat-value"><?= number_format($total_campaigns) ?></div>
                    <div class="stat-subtext text-muted">
                        <span class="text-success fw-semibold"><?= $active_campaigns ?> Active</span> &bull; <?= $paused_campaigns ?> Paused
                    </div>
                </div>
                <div class="stat-icon primary">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Campaigns -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card success h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="stat-label">Active Campaigns</div>
                    <div class="stat-value text-success"><?= number_format($active_campaigns) ?></div>
                    <div class="stat-subtext text-muted">
                        <?= $completed_campaigns ?> marked completed
                    </div>
                </div>
                <div class="stat-icon success">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Budget -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card info h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="stat-label">Total Budget</div>
                    <div class="stat-value"><?= format_currency($total_budget) ?></div>
                    <div class="stat-subtext text-muted">
                        Remaining: <span class="fw-semibold"><?= format_currency($budget_remaining) ?></span>
                    </div>
                </div>
                <div class="stat-icon info">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Spend -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card warning h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="stat-label">Total Amount Spent</div>
                    <div class="stat-value text-primary"><?= format_currency($total_spend) ?></div>
                    <div class="stat-subtext text-muted">
                        <?php 
                            $spendPct = ($total_budget > 0) ? min(100, round(($total_spend / $total_budget) * 100, 1)) : 0;
                        ?>
                        Used <span class="fw-semibold"><?= $spendPct ?>%</span> of overall budget
                    </div>
                </div>
                <div class="stat-icon warning">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- SECONDARY KPI ROW (Impressions, Clicks, Conversions, CTR) -->
<!-- ========================================================= -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
            <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.75rem;">Total Impressions</small>
            <h4 class="fw-bold mb-0 text-dark"><?= number_format($total_impressions) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
            <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.75rem;">Total Clicks</small>
            <h4 class="fw-bold mb-0 text-primary"><?= number_format($total_clicks) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
            <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.75rem;">Total Conversions</small>
            <h4 class="fw-bold mb-0 text-success"><?= number_format($total_conversions) ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-3 border shadow-sm text-center">
            <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.75rem;">Average CTR</small>
            <h4 class="fw-bold mb-0 text-dark"><?= format_percent($overall_ctr) ?></h4>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- CHARTS ROW -->
<!-- ========================================================= -->
<div class="row g-3 mb-4">
    <!-- Spend Comparison Bar Chart -->
    <div class="col-lg-8">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Budget vs. Actual Spend</span>
                <span class="badge bg-light text-secondary border">Campaign Comparison</span>
            </div>
            <div class="card-body">
                <?php if (empty($chartCampaignLabels)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No campaign data available yet.
                    </div>
                <?php else: ?>
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="spendComparisonChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Channel Distribution Pie/Doughnut Chart -->
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pie-chart-fill text-info me-2"></i>Channel Distribution</span>
                <span class="badge bg-light text-secondary border">By Channel</span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <?php if (empty($chartChannelLabels)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-pie-chart fs-1 d-block mb-2"></i>
                        No channels recorded.
                    </div>
                <?php else: ?>
                    <div style="position: relative; height: 260px; width: 100%; max-width: 280px;">
                        <canvas id="channelDistributionChart"></canvas>
                    </div>
                    <div class="small text-muted mt-3 text-center">
                        Total Campaigns: <strong><?= $total_campaigns ?></strong> across <strong><?= count($chartChannelLabels) ?></strong> channels
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- RECENT CAMPAIGNS TABLE (Last 5) -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-clock-history text-secondary me-2"></i>Recent Campaigns</span>
        <a href="campaigns.php" class="btn btn-sm btn-outline-primary">
            View All Campaigns <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Campaign Name</th>
                        <th>Channel</th>
                        <th>Date Range</th>
                        <th>Budget</th>
                        <th>Spend</th>
                        <th style="min-width: 140px;">Budget Utilized</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentCampaigns)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-folder-x fs-3 d-block mb-1"></i>
                                No campaigns found. Click "New Campaign" to create your first one!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentCampaigns as $c): 
                            $budget = (float)$c['budget'];
                            $spent = (float)$c['total_spent'];
                            $pct = ($budget > 0) ? min(100, round(($spent / $budget) * 100)) : 0;
                            $barColor = $pct > 90 ? 'bg-danger' : ($pct > 75 ? 'bg-warning' : 'bg-primary');
                        ?>
                            <tr>
                                <td>
                                    <a href="campaign_detail.php?id=<?= $c['id'] ?>" class="fw-semibold text-decoration-none text-dark d-block">
                                        <?= htmlspecialchars($c['campaign_name']) ?>
                                    </a>
                                    <small class="text-muted">ID: #<?= $c['id'] ?></small>
                                </td>
                                <td><?= render_channel_badge($c['channel']) ?></td>
                                <td>
                                    <small class="text-nowrap">
                                        <?= date('M d, Y', strtotime($c['start_date'])) ?> &ndash; <?= date('M d, Y', strtotime($c['end_date'])) ?>
                                    </small>
                                </td>
                                <td class="fw-semibold"><?= format_currency($budget) ?></td>
                                <td class="text-primary fw-semibold"><?= format_currency($spent) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted fw-semibold" style="font-size: 0.75rem; min-width: 32px;"><?= $pct ?>%</small>
                                    </div>
                                </td>
                                <td><?= render_status_badge($c['status']) ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="campaign_detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="add_metrics.php?campaign_id=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-success" title="Log Metrics">
                                        <i class="bi bi-plus-lg"></i>
                                    </a>
                                    <a href="edit_campaign.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-secondary" title="Edit Campaign">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- CHART.JS INITIALIZATION SCRIPTS -->
<!-- ========================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Spend Comparison Bar Chart
    const ctxSpend = document.getElementById('spendComparisonChart');
    if (ctxSpend) {
        new Chart(ctxSpend, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartCampaignLabels) ?>,
                datasets: [
                    {
                        label: 'Budget (₹)',
                        data: <?= json_encode($chartBudgets) ?>,
                        backgroundColor: 'rgba(99, 102, 241, 0.45)',
                        borderColor: '#6366f1',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Actual Spend (₹)',
                        data: <?= json_encode($chartSpends) ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.65)',
                        borderColor: '#ef4444',
                        borderWidth: 1.5,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 14, font: { family: 'Inter' } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₹' + Number(context.raw).toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value; },
                            font: { family: 'Inter' }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { family: 'Inter', size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Channel Distribution Doughnut Chart
    const ctxChannel = document.getElementById('channelDistributionChart');
    if (ctxChannel) {
        new Chart(ctxChannel, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chartChannelLabels) ?>,
                datasets: [{
                    data: <?= json_encode($chartChannelCounts) ?>,
                    backgroundColor: [
                        '#be185d', // Social Media (Pink)
                        '#4f46e5', // Email (Indigo)
                        '#f59e0b', // PPC (Amber)
                        '#10b981', // SEO (Emerald)
                        '#0284c7'  // Display (Sky Blue)
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { family: 'Inter', size: 11 } }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
