<?php
/**
 * Database Connection & Global Utilities
 * Digital Marketing Campaign Tracker
 * 
 * Uses PDO (PHP Data Objects) for clean, secure, and prepared statement database operations.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration settings
// Default credentials for XAMPP (host: localhost, user: root, password: "")
define('DB_HOST', 'localhost');
define('DB_NAME', 'digital_marketing_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Establishes and returns a PDO database connection.
 * Uses a static variable to maintain a single connection per request (singleton pattern).
 *
 * @return PDO
 */
function get_db_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Friendly error message for beginners & college project evaluation
            die("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f5c2c7; background-color: #f8d7da; color: #842029; border-radius: 8px;'>
                    <h3 style='margin-top: 0;'>⚠️ Database Connection Failed</h3>
                    <p>Could not connect to MySQL database <strong>" . htmlspecialchars(DB_NAME) . "</strong> on <strong>" . htmlspecialchars(DB_HOST) . "</strong>.</p>
                    <p><strong>Troubleshooting Steps:</strong></p>
                    <ol>
                        <li>Make sure your MySQL server is running in XAMPP or WAMP Control Panel.</li>
                        <li>Verify that you have imported <code>database.sql</code> into phpMyAdmin.</li>
                        <li>Check credentials in <code>includes/db_connect.php</code> (default: user 'root', empty password).</li>
                    </ol>
                    <p><small>Error details: " . htmlspecialchars($e->getMessage()) . "</small></p>
                </div>
            ");
        }
    }
    
    return $pdo;
}

// Initialize connection
$pdo = get_db_connection();

/**
 * Sets a flash message to display on the next page view.
 *
 * @param string $type success | danger | warning | info
 * @param string $message The message text
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'text' => $message
    ];
}

/**
 * Retrieves and clears the flash message.
 *
 * @return array|null Flash message array or null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Sanitizes plain user input.
 *
 * @param string $data
 * @return string
 */
function sanitize_input($data) {
    return trim(htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8'));
}

/**
 * Formats a number as a currency string (₹1,234.56).
 *
 * @param float|int $amount
 * @return string
 */
function format_currency($amount) {
    return '₹' . number_format((float)($amount ?? 0), 2);
}

/**
 * Formats a float as percentage string (e.g., 4.25%).
 *
 * @param float|int $val
 * @param int $decimals
 * @return string
 */
function format_percent($val, $decimals = 2) {
    return number_format((float)($val ?? 0), $decimals) . '%';
}

/**
 * Calculates Click-Through Rate (CTR): (Clicks / Impressions) * 100
 *
 * @param int $clicks
 * @param int $impressions
 * @return float
 */
function calculate_ctr($clicks, $impressions) {
    if ($impressions <= 0) return 0.0;
    return round(($clicks / $impressions) * 100, 2);
}

/**
 * Calculates Conversion Rate (CVR): (Conversions / Clicks) * 100
 *
 * @param int $conversions
 * @param int $clicks
 * @return float
 */
function calculate_conversion_rate($conversions, $clicks) {
    if ($clicks <= 0) return 0.0;
    return round(($conversions / $clicks) * 100, 2);
}

/**
 * Returns a styled HTML badge for campaign status.
 *
 * @param string $status
 * @return string
 */
function render_status_badge($status) {
    switch ($status) {
        case 'Active':
            return '<span class="badge bg-success"><i class="bi bi-play-circle me-1"></i>Active</span>';
        case 'Paused':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-pause-circle me-1"></i>Paused</span>';
        case 'Completed':
            return '<span class="badge bg-secondary"><i class="bi bi-check-circle me-1"></i>Completed</span>';
        default:
            return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
    }
}

/**
 * Returns a channel badge with appropriate icon and styling.
 *
 * @param string $channel
 * @return string
 */
function render_channel_badge($channel) {
    $icon = 'bi-broadcast';
    $colorClass = 'badge-channel-general';
    
    switch ($channel) {
        case 'Social Media':
            $icon = 'bi-share-fill';
            $colorClass = 'badge-channel-social';
            break;
        case 'Email':
            $icon = 'bi-envelope-at-fill';
            $colorClass = 'badge-channel-email';
            break;
        case 'PPC':
            $icon = 'bi-cursor-fill';
            $colorClass = 'badge-channel-ppc';
            break;
        case 'SEO':
            $icon = 'bi-search';
            $colorClass = 'badge-channel-seo';
            break;
        case 'Display':
            $icon = 'bi-aspect-ratio-fill';
            $colorClass = 'badge-channel-display';
            break;
    }
    
    return '<span class="badge channel-badge ' . $colorClass . '"><i class="bi ' . $icon . ' me-1"></i>' . htmlspecialchars($channel) . '</span>';
}
