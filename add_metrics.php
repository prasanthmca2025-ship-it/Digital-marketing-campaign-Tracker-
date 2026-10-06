<?php
/**
 * Log Campaign Metrics (CRUD - Create Metric)
 * Digital Marketing Campaign Tracker
 * 
 * Allows users to record daily or periodic performance metrics for any campaign.
 * Automatically computes live CTR and CVR preview on frontend and validates on backend.
 */

$page_title = 'Log Metrics | AdTracker Pro';
$header_title = 'Log Campaign Metrics';
require_once __DIR__ . '/includes/header.php';

// Check if a campaign was pre-selected via query string
$preselected_campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_VALIDATE_INT);

// Fetch all active or available campaigns for the dropdown
try {
    $stmtCampaigns = $pdo->query("SELECT id, campaign_name, channel, status FROM campaigns ORDER BY campaign_name ASC");
    $campaignList = $stmtCampaigns->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

$errors = [];
$campaign_id    = $preselected_campaign_id ?? ($campaignList[0]['id'] ?? '');
$date_recorded  = date('Y-m-d');
$impressions    = '';
$clicks         = '';
$conversions    = '';
$amount_spent   = '';
$engagement_rate= '';

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id    = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);
    $date_recorded  = trim($_POST['date_recorded'] ?? '');
    $impressions    = trim($_POST['impressions'] ?? '');
    $clicks         = trim($_POST['clicks'] ?? '');
    $conversions    = trim($_POST['conversions'] ?? '');
    $amount_spent   = trim($_POST['amount_spent'] ?? '');
    $engagement_rate= trim($_POST['engagement_rate'] ?? '0');

    // Server-Side Validations
    if (!$campaign_id) {
        $errors[] = 'Please select a valid campaign.';
    } else {
        // Verify campaign exists
        $verifyStmt = $pdo->prepare("SELECT id, campaign_name FROM campaigns WHERE id = :id LIMIT 1");
        $verifyStmt->execute([':id' => $campaign_id]);
        $campaignCheck = $verifyStmt->fetch();
        if (!$campaignCheck) {
            $errors[] = 'Selected campaign does not exist.';
        }
    }

    if (empty($date_recorded)) {
        $errors[] = 'Please select the date recorded.';
    }

    if (!is_numeric($impressions) || (int)$impressions < 0) {
        $errors[] = 'Impressions must be a non-negative integer (0 or greater).';
    }

    if (!is_numeric($clicks) || (int)$clicks < 0) {
        $errors[] = 'Clicks must be a non-negative integer (0 or greater).';
    }

    if (!is_numeric($conversions) || (int)$conversions < 0) {
        $errors[] = 'Conversions must be a non-negative integer (0 or greater).';
    }

    if (!is_numeric($amount_spent) || (float)$amount_spent < 0) {
        $errors[] = 'Amount spent must be a non-negative number.';
    }

    if (!is_numeric($engagement_rate) || (float)$engagement_rate < 0 || (float)$engagement_rate > 100) {
        $errors[] = 'Engagement rate must be a percentage between 0 and 100.';
    }

    // Logic consistency checks
    if (is_numeric($clicks) && is_numeric($impressions) && (int)$clicks > (int)$impressions && (int)$impressions > 0) {
        $errors[] = 'Clicks cannot exceed total impressions.';
    }

    // Insert into database if validation passed
    if (empty($errors)) {
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO campaign_metrics 
                (campaign_id, date_recorded, impressions, clicks, conversions, amount_spent, engagement_rate)
                VALUES 
                (:campaign_id, :date_recorded, :impressions, :clicks, :conversions, :amount_spent, :engagement_rate)
            ");

            $insertStmt->execute([
                ':campaign_id'    => $campaign_id,
                ':date_recorded'  => $date_recorded,
                ':impressions'    => (int)$impressions,
                ':clicks'         => (int)$clicks,
                ':conversions'    => (int)$conversions,
                ':amount_spent'   => (float)$amount_spent,
                ':engagement_rate'=> (float)$engagement_rate
            ]);

            set_flash_message('success', 'Performance metrics logged successfully for campaign "' . htmlspecialchars($campaignCheck['campaign_name']) . '"!');
            header('Location: campaign_detail.php?id=' . $campaign_id);
            exit();

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <!-- Navigation -->
        <div class="mb-3">
            <a href="<?= $preselected_campaign_id ? 'campaign_detail.php?id=' . $preselected_campaign_id : 'campaigns.php' ?>" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon success p-2" style="width: 36px; height: 36px; font-size: 1.1rem;">
                        <i class="bi bi-bar-chart-line-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Record Campaign Metrics</h5>
                        <small class="text-muted">Enter daily impressions, clicks, conversions, and ad spend.</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Errors Alert -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <strong>Please fix the errors below:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($campaignList)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-circle me-1"></i> You need to create at least one campaign before logging metrics.
                        <div class="mt-2">
                            <a href="add_campaign.php" class="btn btn-sm btn-primary">Create Campaign</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form action="add_metrics.php" method="POST" id="metricsForm" novalidate>
                        <!-- Campaign Select & Date Recorded -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-7">
                                <label for="campaign_id" class="form-label fw-semibold small">
                                    Target Campaign <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="campaign_id" name="campaign_id" required>
                                    <option value="">-- Choose Campaign --</option>
                                    <?php foreach ($campaignList as $camp): ?>
                                        <option value="<?= $camp['id'] ?>" <?= ((int)$campaign_id === (int)$camp['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($camp['campaign_name']) ?> (<?= $camp['channel'] ?> - <?= $camp['status'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-sm-5">
                                <label for="date_recorded" class="form-label fw-semibold small">
                                    Date Recorded <span class="text-danger">*</span>
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       id="date_recorded" 
                                       name="date_recorded" 
                                       value="<?= htmlspecialchars($date_recorded) ?>" 
                                       required>
                            </div>
                        </div>

                        <!-- Impressions and Clicks -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="metric_impressions" class="form-label fw-semibold small">
                                    Impressions <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       min="0" 
                                       class="form-control" 
                                       id="metric_impressions" 
                                       name="impressions" 
                                       value="<?= htmlspecialchars($impressions) ?>" 
                                       placeholder="e.g. 15000" 
                                       required>
                                <div class="form-text">Total times ads or content were displayed.</div>
                            </div>

                            <div class="col-sm-6">
                                <label for="metric_clicks" class="form-label fw-semibold small">
                                    Clicks <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       min="0" 
                                       class="form-control" 
                                       id="metric_clicks" 
                                       name="clicks" 
                                       value="<?= htmlspecialchars($clicks) ?>" 
                                       placeholder="e.g. 850" 
                                       required>
                                <div class="form-text">Total user click-throughs recorded.</div>
                            </div>
                        </div>

                        <!-- Conversions and Amount Spent -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="metric_conversions" class="form-label fw-semibold small">
                                    Conversions <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       min="0" 
                                       class="form-control" 
                                       id="metric_conversions" 
                                       name="conversions" 
                                       value="<?= htmlspecialchars($conversions) ?>" 
                                       placeholder="e.g. 45" 
                                       required>
                                <div class="form-text">Purchases, signups, or goal completions.</div>
                            </div>

                            <div class="col-sm-6">
                                <label for="amount_spent" class="form-label fw-semibold small">
                                    Amount Spent (₹ INR) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">₹</span>
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control" 
                                           id="amount_spent" 
                                           name="amount_spent" 
                                           value="<?= htmlspecialchars($amount_spent) ?>" 
                                           placeholder="e.g. 2400.00" 
                                           required>
                                </div>
                                <div class="form-text">Ad spend incurred for this period.</div>
                            </div>
                        </div>

                        <!-- Engagement Rate -->
                        <div class="mb-4">
                            <label for="engagement_rate" class="form-label fw-semibold small">
                                Engagement Rate (%)
                            </label>
                            <div class="input-group" style="max-width: 250px;">
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       max="100" 
                                       class="form-control" 
                                       id="engagement_rate" 
                                       name="engagement_rate" 
                                       value="<?= htmlspecialchars($engagement_rate) ?>" 
                                       placeholder="e.g. 4.5">
                                <span class="input-group-text bg-light">%</span>
                            </div>
                            <div class="form-text">Likes, shares, comments, or interactions (optional).</div>
                        </div>

                        <!-- Real-Time Auto-Calculated KPI Preview Box -->
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <div class="small fw-bold text-muted mb-2 text-uppercase">
                                <i class="bi bi-calculator me-1"></i> Live Auto-Calculated Rates (Preview)
                            </div>
                            <div class="row text-center g-2">
                                <div class="col-6">
                                    <div class="p-2 bg-white rounded border">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">Click-Through Rate (CTR)</small>
                                        <span class="fw-bold text-primary fs-5" id="calc_ctr_display">0.00%</span>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">(Clicks &divide; Impressions &times; 100)</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-white rounded border">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">Conversion Rate (CVR)</small>
                                        <span class="fw-bold text-success fs-5" id="calc_cvr_display">0.00%</span>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">(Conversions &divide; Clicks &times; 100)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="campaigns.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-check2-circle me-1"></i> Save Metrics
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
