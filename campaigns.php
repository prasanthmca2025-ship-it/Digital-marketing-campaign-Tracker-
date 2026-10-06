<?php
/**
 * Campaigns List Page (CRUD - Read)
 * Digital Marketing Campaign Tracker
 * 
 * Supports search by name, filtering by channel and status, and provides
 * actions for view, edit, delete, and adding metrics.
 */

$page_title = 'Campaigns List | AdTracker Pro';
$header_title = 'Campaign Management';
require_once __DIR__ . '/includes/header.php';

// Filter parameters from GET request
$search  = trim($_GET['search'] ?? '');
$channel = trim($_GET['channel'] ?? '');
$status  = trim($_GET['status'] ?? '');

// Build query with prepared statement filters
$sql = "
    SELECT 
        c.*,
        COALESCE(SUM(m.amount_spent), 0) AS total_spent,
        COALESCE(SUM(m.conversions), 0)  AS total_conversions,
        COALESCE(SUM(m.clicks), 0)       AS total_clicks,
        COALESCE(SUM(m.impressions), 0)  AS total_impressions,
        COUNT(m.id)                      AS metric_records_count
    FROM campaigns c
    LEFT JOIN campaign_metrics m ON c.id = m.campaign_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND c.campaign_name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($channel)) {
    $sql .= " AND c.channel = :channel";
    $params[':channel'] = $channel;
}

if (!empty($status)) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $status;
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching campaigns: " . htmlspecialchars($e->getMessage()));
}
?>

<!-- ========================================================= -->
<!-- HEADER ACTION ROW -->
<!-- ========================================================= -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">All Marketing Campaigns</h4>
        <p class="text-muted small mb-0">Manage, inspect, and evaluate campaigns across digital channels.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="reports.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> View Report
        </a>
        <a href="add_campaign.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add New Campaign
        </a>
    </div>
</div>

<!-- ========================================================= -->
<!-- FILTER AND SEARCH BAR -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="campaigns.php" class="row g-2 align-items-end">
            <!-- Search by Name -->
            <div class="col-md-4">
                <label for="search" class="form-label small fw-semibold text-muted mb-1">Search Campaign Name</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="e.g. Summer Brand...">
                </div>
            </div>

            <!-- Filter by Channel -->
            <div class="col-sm-6 col-md-3">
                <label for="channel" class="form-label small fw-semibold text-muted mb-1">Filter Channel</label>
                <select class="form-select form-select-sm" id="channel" name="channel">
                    <option value="">All Channels</option>
                    <option value="Social Media" <?= ($channel === 'Social Media') ? 'selected' : '' ?>>Social Media</option>
                    <option value="Email" <?= ($channel === 'Email') ? 'selected' : '' ?>>Email</option>
                    <option value="PPC" <?= ($channel === 'PPC') ? 'selected' : '' ?>>PPC (Search Ads)</option>
                    <option value="SEO" <?= ($channel === 'SEO') ? 'selected' : '' ?>>SEO</option>
                    <option value="Display" <?= ($channel === 'Display') ? 'selected' : '' ?>>Display Ads</option>
                </select>
            </div>

            <!-- Filter by Status -->
            <div class="col-sm-6 col-md-3">
                <label for="status" class="form-label small fw-semibold text-muted mb-1">Filter Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Active" <?= ($status === 'Active') ? 'selected' : '' ?>>Active</option>
                    <option value="Paused" <?= ($status === 'Paused') ? 'selected' : '' ?>>Paused</option>
                    <option value="Completed" <?= ($status === 'Completed') ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($channel) || !empty($status)): ?>
                    <a href="campaigns.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================= -->
<!-- CAMPAIGNS TABLE -->
<!-- ========================================================= -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <span class="fw-bold text-dark">
            <i class="bi bi-table me-2 text-primary"></i>
            Found <?= count($campaigns) ?> Campaign<?= count($campaigns) !== 1 ? 's' : '' ?>
        </span>
        <!-- Optional client-side quick filter input -->
        <div class="d-none d-md-block" style="width: 220px;">
            <input type="text" id="tableLiveSearch" class="form-control form-control-sm" placeholder="Quick in-table search...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="campaignsDataTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Campaign Name</th>
                        <th>Channel</th>
                        <th>Duration</th>
                        <th>Budget</th>
                        <th>Spend</th>
                        <th style="min-width: 130px;">Budget Progress</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2"></i>
                                No campaigns match your current filters.
                                <div class="mt-2">
                                    <a href="campaigns.php" class="btn btn-sm btn-outline-secondary me-2">Clear Filters</a>
                                    <a href="add_campaign.php" class="btn btn-sm btn-primary">Create New Campaign</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $c): 
                            $budget = (float)$c['budget'];
                            $spent = (float)$c['total_spent'];
                            $remaining = $budget - $spent;
                            $pct = ($budget > 0) ? min(100, round(($spent / $budget) * 100)) : 0;
                            $barColor = $pct > 90 ? 'bg-danger' : ($pct > 75 ? 'bg-warning' : 'bg-primary');
                        ?>
                            <tr>
                                <td class="text-muted small">#<?= $c['id'] ?></td>
                                <td>
                                    <a href="campaign_detail.php?id=<?= $c['id'] ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                        <?= htmlspecialchars($c['campaign_name']) ?>
                                    </a>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i><?= date('M d, Y', strtotime($c['created_at'])) ?>
                                    </small>
                                </td>
                                <td><?= render_channel_badge($c['channel']) ?></td>
                                <td>
                                    <div class="small text-nowrap">
                                        <?= date('M d, Y', strtotime($c['start_date'])) ?><br>
                                        <span class="text-muted">to</span> <?= date('M d, Y', strtotime($c['end_date'])) ?>
                                    </div>
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
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        <?= format_currency(max(0, $remaining)) ?> left
                                    </small>
                                </td>
                                <td><?= render_status_badge($c['status']) ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="campaign_detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-primary" title="View Details & Analytics">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="add_metrics.php?campaign_id=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-success" title="Log Daily Metrics">
                                        <i class="bi bi-plus-circle"></i>
                                    </a>
                                    <a href="edit_campaign.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-secondary" title="Edit Campaign">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <!-- Delete Button Triggering Confirmation Modal -->
                                    <button type="button" 
                                            class="btn btn-sm btn-light border text-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteCampaignModal"
                                            data-campaign-id="<?= $c['id'] ?>"
                                            data-campaign-name="<?= htmlspecialchars($c['campaign_name']) ?>"
                                            title="Delete Campaign">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
<!-- DELETE CONFIRMATION MODAL -->
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
                    <p>Are you sure you want to permanently delete <strong id="deleteModalCampaignName" class="text-dark">this campaign</strong>?</p>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Notice:</strong> All logged daily metrics, clicks, impressions, and performance history associated with this campaign will also be deleted. This action cannot be undone.
                    </div>
                    <input type="hidden" name="campaign_id" id="deleteModalCampaignId" value="">
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash me-1"></i> Yes, Delete Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
