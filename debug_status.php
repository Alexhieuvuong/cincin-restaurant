<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if the user is admin
if (!isAdmin()) {
    setFlashMessage('danger', 'Access denied');
    redirect('index.php');
    exit;
}

// Clear logs if requested
if (isset($_GET['clear_logs']) && $_GET['clear_logs'] == 1) {
    $log_file = __DIR__ . '/debug.log';
    file_put_contents($log_file, '');
    setFlashMessage('success', 'Debug logs cleared');
    redirect('debug_status.php');
    exit;
}

// Function to test database connection
function testDatabaseConnection($conn) {
    try {
        $result = $conn->query("SELECT 1");
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Get log file contents
$log_file = __DIR__ . '/debug.log';
$log_contents = file_exists($log_file) ? file_get_contents($log_file) : '';
$log_lines = $log_contents ? explode("\n", $log_contents) : [];
$log_lines = array_filter($log_lines);
$log_lines = array_slice($log_lines, -100); // Show only the last 100 lines

// Test database connection
$db_connection = testDatabaseConnection($conn);

// PHP Info
$php_version = phpversion();
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');

// Session Status
$session_status = session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive';
$session_count = count($_SESSION ?? []);

// Get the disk space usage
$disk_total = disk_total_space(__DIR__);
$disk_free = disk_free_space(__DIR__);
$disk_used = $disk_total - $disk_free;
$disk_percent = round(($disk_used / $disk_total) * 100, 2);

// Get server load (for Unix/Linux systems)
function getServerLoad() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return $load[0];
    }
    return 'N/A';
}

$server_load = getServerLoad();

// Check if output buffering is active
$ob_active = ob_get_level() > 0 ? 'Yes (Level: ' . ob_get_level() . ')' : 'No';

require_once 'includes/header.php';
?>

<h1 class="section-title">System Debug Status</h1>

<div class="debug-status-container">
    <?php displayFlashMessage(); ?>
    
    <div class="debug-grid">
        <div class="debug-card">
            <h2>System Status</h2>
            <div class="status-item <?php echo $db_connection ? 'status-good' : 'status-bad'; ?>">
                <span>Database Connection:</span>
                <span><?php echo $db_connection ? 'Connected' : 'Disconnected'; ?></span>
            </div>
            <div class="status-item">
                <span>PHP Version:</span>
                <span><?php echo $php_version; ?></span>
            </div>
            <div class="status-item">
                <span>Memory Limit:</span>
                <span><?php echo $memory_limit; ?></span>
            </div>
            <div class="status-item">
                <span>Max Execution Time:</span>
                <span><?php echo $max_execution_time; ?> seconds</span>
            </div>
            <div class="status-item">
                <span>Upload Max Filesize:</span>
                <span><?php echo $upload_max_filesize; ?></span>
            </div>
            <div class="status-item">
                <span>POST Max Size:</span>
                <span><?php echo $post_max_size; ?></span>
            </div>
            <div class="status-item">
                <span>Session Status:</span>
                <span><?php echo $session_status; ?></span>
            </div>
            <div class="status-item">
                <span>Session Variables:</span>
                <span><?php echo $session_count; ?></span>
            </div>
            <div class="status-item">
                <span>Server Load:</span>
                <span><?php echo $server_load; ?></span>
            </div>
            <div class="status-item">
                <span>Output Buffering:</span>
                <span><?php echo $ob_active; ?></span>
            </div>
        </div>
        
        <div class="debug-card">
            <h2>Disk Usage</h2>
            <div class="disk-usage">
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo $disk_percent; ?>%">
                        <?php echo $disk_percent; ?>%
                    </div>
                </div>
                <div class="disk-stats">
                    <div>
                        <span>Used:</span>
                        <span><?php echo round($disk_used / (1024 * 1024 * 1024), 2); ?> GB</span>
                    </div>
                    <div>
                        <span>Free:</span>
                        <span><?php echo round($disk_free / (1024 * 1024 * 1024), 2); ?> GB</span>
                    </div>
                    <div>
                        <span>Total:</span>
                        <span><?php echo round($disk_total / (1024 * 1024 * 1024), 2); ?> GB</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="debug-card">
            <h2>Session Data</h2>
            <div class="debug-session">
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
        </div>
        
        <div class="debug-card full-width">
            <div class="debug-header">
                <h2>Debug Logs</h2>
                <a href="debug_status.php?clear_logs=1" class="btn btn-sm btn-danger">Clear Logs</a>
            </div>
            <div class="debug-logs">
                <?php if (empty($log_lines)): ?>
                    <p class="no-logs">No logs found</p>
                <?php else: ?>
                    <pre><?php echo implode("\n", $log_lines); ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.debug-status-container {
    margin-bottom: 40px;
}

.debug-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.debug-card {
    background-color: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.debug-card.full-width {
    grid-column: span 2;
}

.debug-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.status-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f5f5f5;
}

.status-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.status-good {
    color: #28a745;
}

.status-bad {
    color: #dc3545;
}

.disk-usage {
    margin-top: 20px;
}

.progress {
    height: 25px;
    background-color: #f5f5f5;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 15px;
}

.progress-bar {
    height: 100%;
    background-color: var(--primary-color);
    color: white;
    text-align: center;
    line-height: 25px;
}

.disk-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.disk-stats div {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 5px;
}

.disk-stats div span:first-child {
    font-size: 12px;
    color: #666;
}

.disk-stats div span:last-child {
    font-weight: bold;
    margin-top: 5px;
}

.debug-session pre, .debug-logs pre {
    background-color: #f5f5f5;
    padding: 15px;
    border-radius: 5px;
    overflow: auto;
    font-size: 14px;
    line-height: 1.5;
    max-height: 300px;
}

.debug-logs pre {
    max-height: 500px;
}

.no-logs {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

.debug-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.debug-header h2 {
    margin: 0;
    border: none;
    padding: 0;
}

@media screen and (max-width: 768px) {
    .debug-grid {
        grid-template-columns: 1fr;
    }
    
    .debug-card.full-width {
        grid-column: span 1;
    }
    
    .disk-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
require_once 'includes/footer.php';
?> 