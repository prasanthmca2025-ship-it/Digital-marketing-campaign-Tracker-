<?php
/**
 * Add New Campaign (CRUD - Create)
 * Digital Marketing Campaign Tracker
 */

$page_title = 'Add Campaign | AdTracker Pro';
$header_title = 'Add New Campaign';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$campaign_name = '';
$channel = 'Social Media';
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));
$budget = '';
$status = 'Active';

$allowed_channels = ['Social Media', 'Email', 'PPC', 'SEO', 'Display'];
$allowed_statuses = ['Active', 'Paused', 'Completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_name = trim($_POST['campaign_name'] ?? '');
    $channel       = trim($_POST['channel'] ?? '');
    $start_date    = trim($_POST['start_date'] ?? '');
    $end_date      = trim($_POST['end_date'] ?? '');
    $budget        = trim($_POST['budget'] ?? '');
    $status        = trim($_POST['status'] ?? 'Active');

    // Server-Side Validations
    if (empty($campaign_name)) {
        $errors[] = 'Campaign name is required.';
    } elseif (strlen($campaign_name) < 3) {
        $errors[] = 'Campaign name must be at least 3 characters long.';
    }

    if (!in_array($channel, $allowed_channels)) {
        $errors[] = 'Please select a valid marketing channel.';
    }

    if (empty($start_date) || empty($end_date)) {
        $errors[] = 'Both start date and end date are required.';
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $errors[] = 'End date cannot be earlier than the start date.';
    }

    if (!is_numeric($budget) || (float)$budget <= 0) {
        $errors[] = 'Budget must be a positive number greater than 0.';
    }

    if (!in_array($status, $allowed_statuses)) {
        $errors[] = 'Please select a valid campaign status.';
    }

    // Insert into database if validation passes
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO campaigns (user_id, campaign_name, channel, start_date, end_date, budget, status, created_at)
                VALUES (:user_id, :campaign_name, :channel, :start_date, :end_date, :budget, :status, NOW())
            ");
            $stmt->execute([
                ':user_id'       => current_user_id(),
                ':campaign_name' => $campaign_name,
                ':channel'       => $channel,
                ':start_date'    => $start_date,
                ':end_date'      => $end_date,
                ':budget'        => (float)$budget,
                ':status'        => $status
            ]);

            $newCampaignId = $pdo->lastInsertId();
            set_flash_message('success', 'Campaign "' . htmlspecialchars($campaign_name) . '" was created successfully!');
            header('Location: campaign_detail.php?id=' . $newCampaignId);
            exit();

        } catch (PDOException $e) {
            $errors[] = 'Failed to create campaign: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="campaigns.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to All Campaigns
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon primary p-2" style="width: 36px; height: 36px; font-size: 1.1rem;">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Create Digital Marketing Campaign</h5>
                        <small class="text-muted">Define the channel, timeline, and allocated budget.</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Errors Alert -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <strong>Please resolve the following issues:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="add_campaign.php" method="POST" id="campaignForm" novalidate>
                    <!-- Campaign Name -->
                    <div class="mb-3">
                        <label for="campaign_name" class="form-label fw-semibold small">
                            Campaign Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="campaign_name" 
                               name="campaign_name" 
                               value="<?= htmlspecialchars($campaign_name) ?>" 
                               placeholder="e.g. Q4 Black Friday Omnichannel Blitz" 
                               required>
                    </div>

                    <!-- Channel and Status Row -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="channel" class="form-label fw-semibold small">
                                Marketing Channel <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="channel" name="channel" required>
                                <?php foreach ($allowed_channels as $ch): ?>
                                    <option value="<?= $ch ?>" <?= ($channel === $ch) ? 'selected' : '' ?>>
                                        <?= $ch ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-sm-6">
                            <label for="status" class="form-label fw-semibold small">
                                Initial Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <?php foreach ($allowed_statuses as $st): ?>
                                    <option value="<?= $st ?>" <?= ($status === $st) ? 'selected' : '' ?>>
                                        <?= $st ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Date Range Row -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="start_date" class="form-label fw-semibold small">
                                Start Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="start_date" 
                                   name="start_date" 
                                   value="<?= htmlspecialchars($start_date) ?>" 
                                   required>
                        </div>

                        <div class="col-sm-6">
                            <label for="end_date" class="form-label fw-semibold small">
                                End Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="end_date" 
                                   name="end_date" 
                                   value="<?= htmlspecialchars($end_date) ?>" 
                                   required>
                        </div>
                    </div>

                    <!-- Budget Input -->
                    <div class="mb-4">
                        <label for="budget" class="form-label fw-semibold small">
                            Allocated Budget (₹ INR) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="1" 
                                   class="form-control" 
                                   id="budget" 
                                   name="budget" 
                                   value="<?= htmlspecialchars($budget) ?>" 
                                   placeholder="e.g. 50000.00" 
                                   required>
                        </div>
                        <div class="form-text">Total maximum monetary expenditure planned for this campaign.</div>
                    </div>

                    <!-- Submit & Cancel Buttons -->
                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="campaigns.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
