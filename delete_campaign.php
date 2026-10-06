<?php
/**
 * Delete Campaign (CRUD - Delete)
 * Digital Marketing Campaign Tracker
 * 
 * Safely removes a campaign and its associated metrics records.
 */

// Include authentication and database
require_once __DIR__ . '/includes/auth_check.php';

// Accept POST requests for secure deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);

    if ($campaign_id) {
        try {
            // First check if campaign exists and get its name for confirmation
            $stmt = $pdo->prepare("SELECT campaign_name FROM campaigns WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $campaign_id]);
            $campaign = $stmt->fetch();

            if ($campaign) {
                $name = $campaign['campaign_name'];

                // Execute delete (metrics will cascade delete automatically due to FK constraint)
                $delStmt = $pdo->prepare("DELETE FROM campaigns WHERE id = :id");
                $delStmt->execute([':id' => $campaign_id]);

                set_flash_message('success', 'Campaign "' . htmlspecialchars($name) . '" and all associated metrics were deleted successfully.');
            } else {
                set_flash_message('danger', 'Campaign could not be found.');
            }
        } catch (PDOException $e) {
            set_flash_message('danger', 'Failed to delete campaign: ' . $e->getMessage());
        }
    } else {
        set_flash_message('danger', 'Invalid campaign ID provided.');
    }
} else {
    set_flash_message('warning', 'Direct GET access to deletion is not permitted.');
}

// Redirect back to campaigns list
header('Location: campaigns.php');
exit();
