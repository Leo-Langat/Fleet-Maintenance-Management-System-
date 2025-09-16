<?php
class Logger {
    private $logFile = '../logs/system.log';
    private $logDir = '../logs';
    private $maxFileSize = 10485760; // 10MB
    private $maxFiles = 5;

    public function __construct() {
        // Create logs directory if it doesn't exist
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }

        // Set timezone
        date_default_timezone_set('Africa/Nairobi');
    }

    public function log($action, $user_id, $details = '') {
        $timestamp = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        // Format log message
        $logMessage = "[$timestamp] [User ID: $user_id] [IP: $ip_address] [Action: $action] $details\n";
        
        // Check if current log file exceeds max size
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $this->rotateLogs();
        }
        
        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    private function rotateLogs() {
        // Rotate existing log files
        for ($i = $this->maxFiles - 1; $i >= 0; $i--) {
            $currentFile = $i == 0 ? $this->logFile : $this->logFile . '.' . $i;
            $nextFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($currentFile)) {
                if ($i == $this->maxFiles - 1) {
                    // Delete oldest log file
                    unlink($currentFile);
                } else {
                    // Move to next number
                    rename($currentFile, $nextFile);
                }
            }
        }
    }

    public function getLogs($limit = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = [];
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Parse log lines
        foreach (array_slice($lines, -$limit) as $line) {
            if (preg_match('/^\[(.*?)\] \[User ID: (.*?)\] \[IP: (.*?)\] \[Action: (.*?)\] (.*)$/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'user_id' => $matches[2],
                    'ip_address' => $matches[3],
                    'action' => $matches[4],
                    'details' => $matches[5]
                ];
            }
        }
        
        return array_reverse($logs);
    }
}
?> 