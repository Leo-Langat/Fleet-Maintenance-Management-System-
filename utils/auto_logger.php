<?php
require_once __DIR__ . '/logger.php';

class AutoLogger {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    // Authentication Logging
    public function logLogin($user_id, $success) {
        $status = $success ? 'successful' : 'failed';
        $this->logger->log('LOGIN', $user_id, "Login attempt $status");
    }
    
    public function logLogout($user_id) {
        $this->logger->log('LOGOUT', $user_id, 'User logged out');
    }
    
    // User Management Logging
    public function logUserCreation($admin_id, $new_user_id, $username) {
        $this->logger->log('USER_CREATE', $admin_id, "Created new user: $username (ID: $new_user_id)");
    }
    
    public function logUserUpdate($admin_id, $target_user_id, $changes) {
        $this->logger->log('USER_UPDATE', $admin_id, "Updated user ID: $target_user_id - Changes: " . json_encode($changes));
    }
    
    public function logUserDeletion($admin_id, $target_user_id) {
        $this->logger->log('USER_DELETE', $admin_id, "Deleted user ID: $target_user_id");
    }
    
    // Vehicle Management Logging
    public function logVehicleCreation($admin_id, $vehicle_id, $registration_no) {
        $this->logger->log('VEHICLE_CREATE', $admin_id, "Added new vehicle: $registration_no (ID: $vehicle_id)");
    }
    
    public function logVehicleUpdate($admin_id, $vehicle_id, $changes) {
        $this->logger->log('VEHICLE_UPDATE', $admin_id, "Updated vehicle ID: $vehicle_id - Changes: " . json_encode($changes));
    }
    
    public function logVehicleDeletion($admin_id, $vehicle_id) {
        $this->logger->log('VEHICLE_DELETE', $admin_id, "Deleted vehicle ID: $vehicle_id");
    }
    
    // Maintenance Logging
    public function logMaintenanceTaskCreation($admin_id, $task_id, $task_name) {
        $this->logger->log('MAINTENANCE_CREATE', $admin_id, "Created maintenance task: $task_name (ID: $task_id)");
    }
    
    public function logMaintenanceTaskUpdate($admin_id, $task_id, $changes) {
        $this->logger->log('MAINTENANCE_UPDATE', $admin_id, "Updated maintenance task ID: $task_id - Changes: " . json_encode($changes));
    }
    
    public function logMaintenanceTaskDeletion($admin_id, $task_id) {
        $this->logger->log('MAINTENANCE_DELETE', $admin_id, "Deleted maintenance task ID: $task_id");
    }
    
    // Service Center Logging
    public function logServiceCenterCreation($admin_id, $center_id, $center_name) {
        $this->logger->log('SERVICE_CENTER_CREATE', $admin_id, "Added new service center: $center_name (ID: $center_id)");
    }
    
    public function logServiceCenterUpdate($admin_id, $center_id, $changes) {
        $this->logger->log('SERVICE_CENTER_UPDATE', $admin_id, "Updated service center ID: $center_id - Changes: " . json_encode($changes));
    }
    
    public function logServiceCenterDeletion($admin_id, $center_id) {
        $this->logger->log('SERVICE_CENTER_DELETE', $admin_id, "Deleted service center ID: $center_id");
    }
    
    // System Settings Logging
    public function logSettingsUpdate($admin_id, $setting_name, $old_value, $new_value) {
        $this->logger->log('SETTINGS_UPDATE', $admin_id, "Updated setting '$setting_name' from '$old_value' to '$new_value'");
    }
    
    // Error Logging
    public function logError($user_id, $error_message) {
        $this->logger->log('ERROR', $user_id, $error_message);
    }
    
    // General System Action Logging
    public function logSystemAction($user_id, $action, $details = '') {
        $this->logger->log('SYSTEM_' . strtoupper($action), $user_id, $details);
    }

    // Driver Activity Logging
    public function logTripUpdate($driver_id, $vehicle_id, $old_mileage, $new_mileage) {
        $this->logger->log('TRIP_UPDATE', $driver_id, "Updated vehicle (ID: $vehicle_id) mileage from $old_mileage to $new_mileage km");
    }

    public function logPasswordChange($user_id, $success) {
        $status = $success ? 'successful' : 'failed';
        $this->logger->log('PASSWORD_CHANGE', $user_id, "Password change attempt $status");
    }

    
    public function logVehicleAssignment($driver_id, $vehicle_id, $registration_no) {
        $this->logger->log('VEHICLE_ASSIGNMENT', $driver_id, "Assigned to vehicle: $registration_no (ID: $vehicle_id)");
    }

    public function logVehicleStatus($driver_id, $vehicle_id, $status, $details = '') {
        $this->logger->log('VEHICLE_STATUS', $driver_id, "Updated vehicle (ID: $vehicle_id) status to $status. Details: $details");
    }

    public function logMaintenanceBooking($userId, $vehicleId, $maintenanceType, $scheduleDate, $notes = '') {
        $details = "Booked maintenance for vehicle ID: $vehicleId - Type: $maintenanceType, Scheduled: $scheduleDate" . 
                  ($notes ? ", Notes: $notes" : "");
        $this->logger->log('MAINTENANCE_BOOKING', $userId, $details);
    }

    public function logMaintenanceCancellation($userId, $scheduleId) {
        $details = "Cancelled maintenance schedule ID: $scheduleId";
        $this->logger->log('MAINTENANCE_CANCELLATION', $userId, $details);
    }

    public function logMaintenanceRescheduling($userId, $vehicleId, $taskId, $date, $startTime, $endTime) {
        $details = "Rescheduled maintenance for vehicle ID: $vehicleId, task ID: $taskId to $date from $startTime to $endTime";
        $this->logger->log('MAINTENANCE_RESCHEDULING', $userId, $details);
    }
}
?> 