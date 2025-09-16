<?php
// Session timeout configuration
define('SESSION_TIMEOUT', 900); // 90 seconds

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time >= SESSION_TIMEOUT) {
            // Log the timeout
            if (isset($_SESSION['user_id'])) {
                require_once 'auto_logger.php';
                $logger = new AutoLogger();
                $logger->logSystemAction($_SESSION['user_id'], 'SESSION_TIMEOUT', 'Session timed out after ' . floor($inactive_time/60) . ' minutes of inactivity');
            }
            
            // Clear session
            session_unset();
            session_destroy();
            
            // Redirect to login page with timeout message
            header("Location: login.php?timeout=1");
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Function to get remaining session time in minutes
function getRemainingSessionTime() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        $remaining_time = SESSION_TIMEOUT - $inactive_time;
        return max(0, floor($remaining_time / 60));
    }
    return 0;
}
?> 